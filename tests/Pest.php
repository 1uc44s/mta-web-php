<?php

declare(strict_types=1);
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Pest yapılandırması. Test adları gereksinim kimliğiyle başlar: it('FR-SUP-006: …') (docs/RULES.md §11).
 */

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/**
 * Faz 4 yardımcısı: rolleri seed eder, 2FA'sı onaylı ve e-postası doğrulanmış personel oluşturur.
 *
 * @param  string  ...$roles  Rol slug'ları (config/roles.php)
 */
function staffUser(string ...$roles): User
{
    if (Role::query()->doesntExist()) {
        (new AccessControlSeeder)->run();
    }

    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => encrypt(json_encode(['kod-1', 'kod-2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    foreach ($roles as $slug) {
        $role = Role::query()->where('slug', $slug)->firstOrFail();
        DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $role->id, 'created_at' => now()]);
    }

    $user->flushAuthz();

    return $user;
}

/** Parola onayı (password.confirm) yapılmış oturum verisi. */
function confirmedPassword(): array
{
    return ['auth.password_confirmed_at' => now()->getTimestamp()];
}
