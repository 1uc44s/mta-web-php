<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

/**
 * docs/ROUTES.md §4: DELETE /user/two-factor-authentication personel için engellenir (FR-AUTH-017).
 * Personel cihazını kaybettiyse süper admin MFA sıfırlaması yapar (ADMIN §6 adım 8).
 */
final class DisableTwoFactorForPlayers extends DisableTwoFactorAuthentication
{
    /** @param  mixed  $user */
    public function __invoke($user): void
    {
        if ($user instanceof User && $user->isStaff()) {
            throw ValidationException::withMessages(['two_factor' => __('auth.two_factor_staff_required')]);
        }

        parent::__invoke($user);
    }
}
