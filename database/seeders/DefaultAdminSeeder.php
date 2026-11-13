<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Admin\AssignRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Yerel varsayılan süper admin — docs/DATABASE.md §12, ADMIN §4.4. Proje sahibinin isteğiyle geliştirme kolaylığı için.
 * YALNIZCA `APP_ENV=local`: production'da çalışmayı reddeder (orada `php artisan mrp:make-super-admin` kullanılır).
 * Bilgiler `.env` ile değiştirilebilir (DEFAULT_ADMIN_*). Parola kurallarını atlar; ilk girişte 2FA kurulumu zorunludur.
 */
final class DefaultAdminSeeder extends Seeder
{
    public function run(AssignRole $assign): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DefaultAdminSeeder production ortamında çalıştırılamaz.');
        }

        /** @var array{username: string, email: string, password: string} $cfg */
        $cfg = config('roleplay.default_admin');
        $email = mb_strtolower(trim($cfg['email']));

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $user = new User;
            $user->forceFill([
                'username' => $cfg['username'],
                'email' => $email,
                'password' => Hash::make($cfg['password']),
                'email_verified_at' => now(),
            ])->save();
        }

        $role = Role::query()->where('slug', Role::SUPER_ADMIN)->firstOrFail();
        if (! $user->roles()->whereKey($role->id)->exists()) {
            $assign->handle($user, $user, $role, 'Yerel varsayılan süper admin (DefaultAdminSeeder)', null, breakGlass: true);
        }
    }
}
