<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Admin\AssignRole;
use App\Models\Role;
use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Break-glass — docs/ADMIN.md §4.3. Mevcut kullanıcıya süper admin rolü verir ya da yeni kullanıcı oluşturur.
 * Parola yalnızca interaktif sorulur (komut satırı geçmişine düşmez). İlk girişte MFA kurulumu zorunludur.
 */
#[Signature('mrp:make-super-admin {email : Hesabın e-posta adresi}')]
#[Description('Bir hesaba süper admin rolü verir (yoksa oluşturur) — acil erişim')]
final class MakeSuperAdminCommand extends Command
{
    public function handle(AssignRole $assign): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email:rfc,strict', 'max:254']])->fails()) {
            $this->error('Geçersiz e-posta.');

            return self::INVALID;
        }

        $role = Role::query()->where('slug', Role::SUPER_ADMIN)->first();

        if ($role === null) {
            $this->error('Roller seed edilmemiş: php artisan db:seed --class=AccessControlSeeder');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first() ?? $this->createUser($email);

        if ($user === null) {
            return self::FAILURE;
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save(); // CLI erişimi sunucu sahipliğini kanıtlar
        }

        $assign->handle($user, $user, $role, 'Break-glass: CLI ile süper admin ataması', null, breakGlass: true);

        $this->info("{$user->username} artık süper admin. İlk /admin girişinde 2FA kurulumu istenecek.");

        return self::SUCCESS;
    }

    private function createUser(string $email): ?User
    {
        $username = (string) $this->ask('Yeni hesap için kullanıcı adı');
        $password = (string) $this->secret('Parola (en az 10 karakter)');
        $confirm = (string) $this->secret('Parola (tekrar)');

        $validator = Validator::make(
            ['username' => $username, 'password' => $password, 'password_confirmation' => $confirm],
            [
                'username' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9_.]+$/', 'unique:users,username'],
                'password' => PasswordRules::for($username, $email),
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return null;
        }

        return User::query()->create(['username' => $username, 'email' => $email, 'password' => $password]);
    }
}
