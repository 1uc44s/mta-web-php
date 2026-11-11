<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Validation\Rules\Password;

/**
 * Parola politikası (docs/SRS.md FR-AUTH-002): 10–128 karakter, sızıntı kontrolü (k-anonymity),
 * kullanıcı adını veya e-postanın yerel kısmını içeremez.
 */
final class PasswordRules
{
    /** @return list<mixed> */
    public static function for(?string $username, ?string $email): array
    {
        return [
            'required',
            'string',
            'confirmed',
            Password::min(10)->max(128)->uncompromised(),
            static function (string $attribute, mixed $value, Closure $fail) use ($username, $email): void {
                $password = mb_strtolower((string) $value);
                $local = $email !== null ? mb_strtolower((string) strstr($email, '@', true)) : '';

                foreach (array_filter([mb_strtolower((string) $username), $local], fn (string $s): bool => mb_strlen($s) >= 3) as $part) {
                    if (str_contains($password, $part)) {
                        $fail(__('auth.password_contains_identity'));

                        return;
                    }
                }
            },
        ];
    }
}
