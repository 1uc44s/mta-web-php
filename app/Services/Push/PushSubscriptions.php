<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use App\Support\UserAgentSummary;
use Illuminate\Validation\ValidationException;

/**
 * Web Push abonelikleri — docs/DATABASE.md §3.11a, ROUTES §5.1. Kullanıcı başına en fazla
 * `roleplay.push.max_subscriptions_per_user`; endpoint tekil (başka kullanıcıya aitse devralınır — tarayıcı tek sahiplidir).
 * 404/410 yanıtında abonelik silinir; ardışık 5 hatada da silinir.
 */
final class PushSubscriptions
{
    public const MAX_FAILURES = 5;

    public function __construct(private readonly SettingsRepository $settings, private readonly PushTransport $transport) {}

    public function available(): bool
    {
        return $this->settings->bool('features.push')
            && (string) config('roleplay.push.public_key') !== ''
            && (string) config('roleplay.push.private_key') !== '';
    }

    /** @param  array{endpoint: string, keys: array{p256dh: string, auth: string}, contentEncoding?: string|null}  $data */
    public function subscribe(User $user, array $data, ?string $userAgent): PushSubscription
    {
        $hash = hash('sha256', $data['endpoint']);

        $existing = PushSubscription::query()->where('endpoint_hash', $hash)->first();
        if ($existing === null && PushSubscription::query()->where('user_id', $user->id)->count() >= (int) config('roleplay.push.max_subscriptions_per_user', 10)) {
            throw ValidationException::withMessages(['endpoint' => __('notifications.push.limit')]);
        }

        return PushSubscription::query()->updateOrCreate(['endpoint_hash' => $hash], [
            'user_id' => $user->id,
            'endpoint' => $data['endpoint'],
            'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
            'content_encoding' => ($data['contentEncoding'] ?? null) === 'aesgcm' ? 'aesgcm' : 'aes128gcm',
            'user_agent_summary' => UserAgentSummary::from($userAgent),
            'failures' => 0,
        ]);
    }

    public function unsubscribe(User $user, string $endpoint): bool
    {
        return PushSubscription::query()
            ->where('user_id', $user->id)
            ->where('endpoint_hash', hash('sha256', $endpoint))
            ->delete() > 0;
    }

    /** @param  array{title: string, body: string|null, url: string, tag: string}  $message @return int gönderilen sayısı */
    public function sendToUser(User $user, array $message): int
    {
        if (! $this->available()) {
            return 0;
        }

        $payload = (string) json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sent = 0;

        foreach (PushSubscription::query()->where('user_id', $user->id)->get() as $subscription) {
            $status = $this->transport->deliver($subscription, $payload);

            if ($status >= 200 && $status < 300) {
                $subscription->forceFill(['failures' => 0, 'last_used_at' => now()])->save();
                $sent++;
            } elseif (in_array($status, [404, 410], true) || $subscription->failures + 1 >= self::MAX_FAILURES) {
                $subscription->delete();
            } else {
                $subscription->increment('failures');
            }
        }

        return $sent;
    }
}
