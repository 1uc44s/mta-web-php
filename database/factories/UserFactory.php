<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Test/geliştirme kullanıcıları (docs/DATABASE.md §3.1). Varsayılan parola: UserFactory::PASSWORD.
 *
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    public const PASSWORD = 'Sifre-Test-2026';

    protected static ?string $password = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'username' => Str::lower(Str::random(3)).fake()->unique()->numberBetween(1000, 999999),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make(self::PASSWORD),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    public function suspended(?DateTimeInterface $until = null, string $reason = 'Test askısı'): static
    {
        return $this->state(fn (): array => [
            'status' => UserStatus::Suspended,
            'suspended_until' => $until,
            'suspension_reason' => $reason,
        ]);
    }
}
