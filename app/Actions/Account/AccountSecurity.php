<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use App\Services\Messaging\MessageDispatcher;
use App\Services\Security\SecurityEventLogger;
use App\Support\EmailMask;
use App\Support\IpMasker;
use App\Support\UserAgentSummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Hesap güvenliği — docs/SRS.md FR-AUTH-014/015/018/019.
 */
final class AccountSecurity
{
    public const EMAIL_TOKEN_MINUTES = 60;

    public function __construct(private readonly SecurityEventLogger $security, private readonly MessageDispatcher $messages) {}

    /** FR-AUTH-019: mevcut parola zorunlu (FormRequest), diğer oturumlar kapanır, güvenlik e-postası gider. */
    public function changePassword(User $user, string $password, Request $request): void
    {
        DB::transaction(function () use ($user, $password, $request): void {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        });

        $request->session()->regenerate();
        $this->security->record($user, 'password_changed', $request);

        $this->messages->send('security.password_changed', $user, [
            'user.username' => $user->username,
            'event.time' => now()->timezone('Europe/Istanbul')->format('d.m.Y H:i'),
            'event.device' => UserAgentSummary::from($request->userAgent()) ?? 'bilinmeyen cihaz',
            'link.security' => route('panel.security'),
        ]);
    }

    /**
     * FR-AUTH-018: token hash'i saklanır; onay bağlantısı YENİ adrese gider. Bekleyen eski talepler iptal edilir.
     * Adres başka hesapta kayıtlıysa yanıt değişmez ve e-posta gönderilmez (SEC-005); onayda tekrar kontrol edilir.
     */
    public function requestEmailChange(User $user, string $newEmail, Request $request): void
    {
        $newEmail = mb_strtolower(trim($newEmail));

        if ($newEmail === $user->email) {
            throw ValidationException::withMessages(['email' => 'Yeni adres mevcut adresinle aynı.']);
        }

        $token = Str::random(64);

        DB::transaction(function () use ($user, $newEmail, $token): void {
            DB::table('email_change_requests')->where('user_id', $user->id)->whereNull('confirmed_at')->whereNull('cancelled_at')
                ->update(['cancelled_at' => now()]);
            DB::table('email_change_requests')->insert([
                'user_id' => $user->id, 'new_email' => $newEmail, 'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(self::EMAIL_TOKEN_MINUTES), 'created_at' => now(),
            ]);
        });

        $this->security->record($user, 'email_change_requested', $request);

        if (! User::query()->where('email', $newEmail)->exists()) {
            $this->messages->send('security.email_change_requested', Notification::route('mail', $newEmail), [
                'user.username' => $user->username,
                'link.confirm' => route('panel.security.email.confirm', $token),
            ]);
        }
    }

    /** Bekleyen (onaylanmamış, süresi dolmamış) yeni e-posta adresi. */
    public function pendingEmail(User $user): ?string
    {
        $email = DB::table('email_change_requests')->where('user_id', $user->id)
            ->whereNull('confirmed_at')->whereNull('cancelled_at')->where('expires_at', '>', now())
            ->latest('id')->value('new_email');

        return $email !== null ? (string) $email : null;
    }

    /** Geçerli (süresi dolmamış, kullanılmamış) talep. */
    public function findEmailChange(string $token): ?object
    {
        return DB::table('email_change_requests')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('confirmed_at')->whereNull('cancelled_at')
            ->where('expires_at', '>', now())
            ->first(['id', 'user_id', 'new_email']);
    }

    public function confirmEmailChange(User $user, string $token, Request $request): void
    {
        $change = $this->findEmailChange($token);

        if ($change === null || (int) $change->user_id !== $user->id) {
            throw ValidationException::withMessages(['token' => 'Bağlantı geçersiz veya süresi dolmuş.']);
        }

        $newEmail = (string) $change->new_email;
        if (User::query()->where('email', $newEmail)->exists()) {
            throw ValidationException::withMessages(['token' => 'Bu e-posta adresi kullanılamıyor.']);
        }

        $old = $user->email;

        DB::transaction(function () use ($user, $change, $newEmail): void {
            $user->forceFill(['email' => $newEmail, 'email_verified_at' => now()])->save();
            DB::table('email_change_requests')->where('id', $change->id)->update(['confirmed_at' => now()]);
        });

        $this->security->record($user, 'email_changed', $request);

        $this->messages->send('security.email_changed_notice', Notification::route('mail', $old), [
            'user.username' => $user->username,
            'new_email_masked' => EmailMask::mask($user->email),
            'link.support' => route('public.contact'),
        ]);
    }

    /**
     * FR-AUTH-014: oturumlar (IP maskeli, UA özeti).
     *
     * @return Collection<int, array{id: string, ip: string|null, device: string, last_active: CarbonImmutable, current: bool}>
     */
    public function sessions(User $user, string $currentId): Collection
    {
        return DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->get()
            ->map(fn ($s): array => [
                'id' => (string) $s->id,
                'ip' => IpMasker::mask($s->ip_address),
                'device' => UserAgentSummary::from($s->user_agent) ?? 'Bilinmeyen cihaz',
                'last_active' => CarbonImmutable::createFromTimestamp((int) $s->last_activity),
                'current' => $s->id === $currentId,
            ]);
    }

    /** FR-AUTH-015: tekil / diğerleri / hepsi. Tekil kapatmada mevcut oturum kapatılamaz (onun için "hepsi"). */
    public function revokeSessions(User $user, Request $request, string $scope, ?string $sessionId = null): int
    {
        $query = DB::table('sessions')->where('user_id', $user->id);
        $current = $request->session()->getId();

        $deleted = match ($scope) {
            'one' => $query->where('id', $sessionId)->where('id', '!=', $current)->delete(),
            'others' => $query->where('id', '!=', $current)->delete(),
            default => $query->delete(),
        };

        $this->security->record($user, 'sessions_revoked', $request, ['scope' => $scope, 'count' => $deleted]);

        return $deleted;
    }
}
