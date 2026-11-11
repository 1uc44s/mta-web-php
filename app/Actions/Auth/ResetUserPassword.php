<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Messaging\MessageDispatcher;
use App\Services\Security\SecurityEventLogger;
use App\Support\PasswordRules;
use App\Support\UserAgentSummary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Fortify parola sıfırlama (docs/SRS.md FR-AUTH-013): yeni parola + tüm oturumlar ve remember token geçersiz.
 */
final class ResetUserPassword implements ResetsUserPasswords
{
    public function __construct(private readonly SecurityEventLogger $security) {}

    /** @param array<string, string> $input */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, ['password' => PasswordRules::for($user->username, $user->email)])->validate();

        DB::transaction(function () use ($user, $input): void {
            $user->forceFill([
                'password' => $input['password'],
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        $this->security->record($user, 'password_reset', request());
        app(MessageDispatcher::class)->send('security.password_changed', $user, [
            'user.username' => $user->username,
            'event.time' => now()->timezone('Europe/Istanbul')->format('d.m.Y H:i'),
            'event.device' => UserAgentSummary::from(request()->userAgent()) ?? 'bilinmeyen cihaz',
            'link.security' => route('panel.dashboard'),
        ]);
    }
}
