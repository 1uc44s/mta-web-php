<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/*
 * docs/ADMIN.md §3 rol matrisi = config/roles.php = seed edilmiş DB; ve her izin için pozitif + negatif yetki kontrolü
 * (ROADMAP Faz 4 çıkış kriteri).
 */

uses(RefreshDatabase::class);

/** @return array<string, array{permissions: list<string>, scoped: list<string>}> */
function adminDocMatrix(): array
{
    $doc = (string) file_get_contents(dirname(__DIR__, 3).'/docs/ADMIN.md');
    $section = explode('## 4. Süper Admin', explode('## 3. İzin kataloğu', $doc)[1])[0];
    $cols = ['admin', 'community_manager', 'game_master', 'finance_manager', 'moderator', 'faction_manager', 'support_agent', 'content_editor'];
    $out = array_fill_keys($cols, ['permissions' => [], 'scoped' => []]);

    foreach (explode("\n", $section) as $line) {
        if (! preg_match('/^\| `([a-z_]+\.[a-z_.]+)`/', $line, $m)) {
            continue;
        }
        $cells = array_slice(array_map('trim', explode('|', trim($line, ' |'))), 2, 8);
        foreach ($cells as $i => $cell) {
            if (str_contains($cell, '✅') || str_contains($cell, '🔸')) {
                $out[$cols[$i]]['permissions'][] = $m[1];
            }
            if (str_contains($cell, '🔸')) {
                $out[$cols[$i]]['scoped'][] = $m[1];
            }
        }
    }

    return $out;
}

it('RULES-1 / SEC-010: config/roles.php ADMIN.md §3 matrisiyle birebir aynı', function (): void {
    foreach (adminDocMatrix() as $slug => $expected) {
        expect(config("roles.{$slug}.permissions"))->toEqualCanonicalizing($expected['permissions'], "{$slug} izinleri")
            ->and(config("roles.{$slug}.scoped"))->toEqualCanonicalizing($expected['scoped'], "{$slug} kapsamlı izinleri");
    }
    expect(config('roles.super_admin.permissions'))->toBe(['*']);
});

it('SEC-010: seeder DB matrisini config ile senkronlar ve idempotenttir', function (): void {
    (new AccessControlSeeder)->run();
    (new AccessControlSeeder)->run();

    expect(Role::query()->count())->toBe(9);

    foreach (config('roles') as $slug => $meta) {
        if ($slug === 'super_admin') {
            continue;
        }
        $db = Role::query()->where('slug', $slug)->firstOrFail()->permissions()->pluck('key')->all();
        expect($db)->toEqualCanonicalizing($meta['permissions'], $slug);
    }
});

it('SEC-010: her sistem rolü yalnızca matristeki izinlere sahiptir (pozitif + negatif)', function (): void {
    $catalog = array_keys(config('permissions'));

    foreach (config('roles') as $slug => $meta) {
        $user = staffUser($slug);
        foreach ($catalog as $permission) {
            $expected = $slug === 'super_admin' || in_array($permission, $meta['permissions'], true);
            expect($user->can($permission))->toBe($expected, "{$slug} → {$permission}");
        }
    }
});

it('ADMIN-2: oyuncu (rolsüz) hiçbir izne sahip değildir ve personel sayılmaz', function (): void {
    staffUser(); // seed
    $player = User::factory()->create();

    expect($player->isStaff())->toBeFalse();
    foreach (array_keys(config('permissions')) as $permission) {
        expect($player->can($permission))->toBeFalse($permission);
    }
});

it('ADMIN-6: süresi dolmuş rol izin vermez', function (): void {
    $user = staffUser('admin');
    DB::table('user_roles')->where('user_id', $user->id)->update(['expires_at' => now()->subMinute()]);
    $user->flushAuthz();

    expect($user->isStaff())->toBeFalse()->and($user->can('users.read'))->toBeFalse();
});
