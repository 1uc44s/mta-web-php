<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
 * docs/SRS.md SEC-010, FR-ADM-001/002: her admin.* rotası × (misafir, oyuncu, 2FA'sız personel, izinsiz personel).
 * Yeni bir admin rotası eklendiğinde bu test otomatik olarak onu da kapsar.
 */

uses(RefreshDatabase::class);

/** @return list<array{route: RouteObject, method: string, url: string, can: string|null}> */
function adminRouteCases(): array
{
    $target = User::factory()->create();
    $role = Role::query()->where('slug', 'moderator')->firstOrFail();
    $branch = Branch::query()->firstOrFail();
    $log = app(AuditLogger::class)->record(AuditAction::BranchesUpdated, $branch, [], null, null, 'system');

    $params = ['user' => $target->ulid, 'role' => $role->id, 'branch' => $branch->id, 'log' => $log->ulid, 'group' => 'site', 'uuid' => 'yok'];

    $cases = [];
    foreach (Route::getRoutes() as $route) {
        if (! str_starts_with((string) $route->getName(), 'admin.')) {
            continue;
        }
        $can = collect($route->gatherMiddleware())->first(fn ($m) => is_string($m) && str_starts_with($m, 'can:'));
        $url = route((string) $route->getName(), array_intersect_key($params, array_flip($route->parameterNames())));
        $cases[] = ['route' => $route, 'method' => in_array('POST', $route->methods(), true) ? 'POST' : 'GET', 'url' => $url, 'can' => $can ? substr($can, 4) : null];
    }

    return $cases;
}

beforeEach(function (): void {
    staffUser(); // rolleri seed et
});

it('SEC-010: admin rotası kataloğu boş değildir ve dashboard dışındaki her rota can: taşır', function (): void {
    $cases = adminRouteCases();
    expect(count($cases))->toBeGreaterThan(25);

    foreach ($cases as $case) {
        if ($case['route']->getName() !== 'admin.dashboard') {
            expect($case['can'])->not->toBeNull($case['route']->getName().' can: middleware taşımıyor');
        }
    }
});

it('FR-ADM-001: misafir her admin rotasında girişe yönlendirilir', function (): void {
    foreach (adminRouteCases() as $case) {
        $this->call($case['method'], $case['url'])->assertRedirect(route('login'));
    }
});

it('FR-ADM-001: personel olmayan doğrulanmış oyuncu her admin rotasında 404 alır', function (): void {
    $player = User::factory()->create();
    foreach (adminRouteCases() as $case) {
        $this->actingAs($player)->call($case['method'], $case['url'])->assertNotFound();
    }
});

it('FR-AUTH-017: 2FA kurulmamış personel 2FA kurulum sayfasına yönlendirilir', function (): void {
    $staff = staffUser('admin');
    $staff->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();

    foreach (adminRouteCases() as $case) {
        $this->actingAs($staff)->call($case['method'], $case['url'])->assertRedirect(route('panel.security.two-factor'));
    }
});

it('FR-ADM-002: izni olmayan personel can: korumalı her rotada 403 alır', function (): void {
    // İzinsiz özel personel rolü: /admin'e girer ama hiçbir modüle erişemez.
    $role = Role::query()->create(['slug' => 'bos_personel', 'name' => 'Boş', 'priority' => 10, 'is_staff' => true]);
    $staff = staffUser();
    DB::table('user_roles')->insert(['user_id' => $staff->id, 'role_id' => $role->id, 'created_at' => now()]);
    $staff->flushAuthz();

    $this->actingAs($staff)->withSession(confirmedPassword())->get(route('admin.dashboard'))->assertOk();

    foreach (adminRouteCases() as $case) {
        if ($case['can'] === null) {
            continue;
        }
        $this->actingAs($staff)->withSession(confirmedPassword())->call($case['method'], $case['url'])
            ->assertForbidden();
    }
});

it('FR-ADM-002: süper admin her GET admin sayfasını görüntüleyebilir', function (): void {
    $super = staffUser('super_admin');

    foreach (adminRouteCases() as $case) {
        if ($case['method'] === 'GET') {
            $this->actingAs($super)->get($case['url'])->assertOk();
        }
    }
});
