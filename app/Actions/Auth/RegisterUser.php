<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Consent;
use App\Models\User;
use App\Services\Messaging\MessageDispatcher;
use App\Services\Security\SecurityEventLogger;
use App\Services\Settings\SettingsRepository;
use App\Support\IpMasker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Kayıt (docs/SRS.md FR-AUTH-001..005, US-AUTH-01). E-posta zaten kayıtlıysa yeni hesap açılmaz,
 * hesap sahibine bilgi e-postası gider; çağırana dönen sonuç her iki durumda aynıdır (SEC-005).
 */
final class RegisterUser
{
    public function __construct(
        private readonly SecurityEventLogger $security,
        private readonly SettingsRepository $settings,
        private readonly MessageDispatcher $messages,
    ) {}

    /** @param array{username: string, email: string, password: string} $data */
    public function handle(array $data, Request $request): void
    {
        $existing = User::query()->where('email', $data['email'])->first();

        if ($existing !== null) {
            $this->messages->send('auth.already_registered', $existing, [
                'link.login' => route('login'),
                'link.reset' => route('password.request'),
            ]);

            return;
        }

        $user = DB::transaction(function () use ($data, $request): User {
            $user = User::query()->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $version = (string) $this->settings->get('rules.current_version', 'v1');
            foreach (['terms', 'kvkk_ack'] as $type) {
                Consent::query()->create([
                    'user_id' => $user->id, 'type' => $type, 'version' => $version,
                    'granted' => true, 'ip_masked' => IpMasker::mask($request->ip()),
                ]);
            }

            return $user;
        });

        $this->security->record($user, 'registered', $request);
        $user->sendEmailVerificationNotification();
    }
}
