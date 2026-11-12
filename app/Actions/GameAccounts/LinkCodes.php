<?php

declare(strict_types=1);

namespace App\Actions\GameAccounts;

use App\Models\GameAccountLink;
use App\Models\GameLinkCode;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bağlama kodu yaşam döngüsü — docs/SRS.md FR-ACC-001..003/007, ARCHITECTURE §7.4.
 * Kod yalnızca üretim yanıtında düz metin görünür; DB'de `HMAC-SHA256(code, APP_KEY türevi)` saklanır.
 * Kullanıcı başına aynı amaçla tek aktif kod (yenisi eskisini iptal eder).
 */
final class LinkCodes
{
    public const PURPOSE = 'account_link';

    /** Faz 8 — ARCHITECTURE §7.6 cihaz/serial ilişkilendirme. */
    public const DEVICE_PURPOSE = 'device_link';

    public function __construct(private readonly SettingsRepository $settings) {}

    public static function normalize(string $input): string
    {
        return strtoupper((string) preg_replace('/[\s-]+/', '', $input));
    }

    public static function isWellFormed(string $code): bool
    {
        $alphabet = preg_quote((string) config('roleplay.link_code.alphabet'), '/');

        return preg_match('/^['.$alphabet.']{'.(int) config('roleplay.link_code.length').'}$/', $code) === 1;
    }

    public static function hash(string $code): string
    {
        $key = hash_hmac('sha256', 'mrp.game-link-code.v1', (string) config('app.key'), true);

        return hash_hmac('sha256', self::normalize($code), $key);
    }

    public static function display(string $code): string
    {
        return implode('-', str_split($code, 4));
    }

    public function maxLinks(): int
    {
        return max(1, (int) $this->settings->get('accounts.max_links_per_user', 3));
    }

    public function activeLinkCount(User $user): int
    {
        return GameAccountLink::query()->where('user_id', $user->id)->whereIn('status', GameAccountLink::ACTIVE)->count();
    }

    /** @return array{code: string, display: string, expires_at: CarbonImmutable} */
    public function issue(User $user, string $purpose = self::PURPOSE): array
    {
        if ($purpose === self::PURPOSE && $this->activeLinkCount($user) >= $this->maxLinks()) {
            throw ValidationException::withMessages(['code' => __('game.errors.limit', ['max' => $this->maxLinks()])]);
        }

        if ($purpose === self::DEVICE_PURPOSE && $this->activeLinkCount($user) === 0) {
            throw ValidationException::withMessages(['code' => __('game.errors.no_link_for_device')]);
        }

        $alphabet = (string) config('roleplay.link_code.alphabet');
        $length = (int) config('roleplay.link_code.length');
        $expiresAt = CarbonImmutable::now()->addMinutes((int) config('roleplay.link_code.ttl_minutes'));

        return DB::transaction(function () use ($user, $purpose, $alphabet, $length, $expiresAt): array {
            $this->cancelPending($user, $purpose);

            do {
                $code = '';
                for ($i = 0; $i < $length; $i++) {
                    $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                }
                $hash = self::hash($code);
            } while (GameLinkCode::query()->where('code_hash', $hash)->exists());

            GameLinkCode::query()->create([
                'user_id' => $user->id,
                'purpose' => $purpose,
                'code_hash' => $hash,
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]);

            return ['code' => $code, 'display' => self::display($code), 'expires_at' => $expiresAt];
        });
    }

    public function cancelPending(User $user, string $purpose = self::PURPOSE): int
    {
        return GameLinkCode::query()->where('user_id', $user->id)->where('purpose', $purpose)
            ->whereNull('used_at')->whereNull('cancelled_at')->where('expires_at', '>', now())
            ->update(['cancelled_at' => now()]);
    }

    public function current(User $user, string $purpose = self::PURPOSE): ?GameLinkCode
    {
        return GameLinkCode::query()->where('user_id', $user->id)->where('purpose', $purpose)->latest('id')->first();
    }

    /**
     * FR-ACC-007 polling yanıtı. Kod değeri asla dönmez.
     *
     * @return array{state: string, expires_in: int, account_name?: string}
     */
    public function status(User $user, string $purpose = self::PURPOSE): array
    {
        $code = $this->current($user, $purpose);

        if ($code === null || $code->cancelled_at !== null) {
            return ['state' => 'none', 'expires_in' => 0];
        }

        if ($code->used_at !== null) {
            $name = DB::table('mta_accounts')->where('id', $code->used_by_mta_account_id)->value('account_name');

            return ['state' => 'linked', 'expires_in' => 0, 'account_name' => is_string($name) ? $name : ''];
        }

        if (! $code->expires_at->isFuture()) {
            return ['state' => 'expired', 'expires_in' => 0];
        }

        return ['state' => 'pending', 'expires_in' => (int) now()->diffInSeconds($code->expires_at, absolute: true)];
    }
}
