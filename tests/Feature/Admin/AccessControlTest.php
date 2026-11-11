<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Models\AdminActivityLog;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

/* Faz 4 iş kuralları — docs/ADMIN.md §4–§6, ROUTES §6.2/6.10/6.11, SRS FR-ADM-003/004/006/009. */

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
});

function role(string $slug): Role
{
    return Role::query()->where('slug', $slug)->firstOrFail();
}

// ---- Rol atama / kaldırma -------------------------------------------------

it('ADMIN-6: admin daha düşük öncelikli rolü atar; audit ve bildirim yazılır', function (): void {
    $admin = staffUser('admin');
    $target = User::factory()->create();

    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.staff.roles.assign', $target), ['role_id' => role('moderator')->id, 'reason' => 'Moderasyon ekibine katıldı'])
        ->assertSessionHasNoErrors();

    expect($target->fresh()->isStaff())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'roles.assigned')->where('target_id', $target->id)->exists())->toBeTrue();
});

it('ADMIN-4.2: hiyerarşi — admin kendi önceliğine eşit/üstü rolü atayamaz, süper admin rolünü yalnızca süper admin atar', function (): void {
    $admin = staffUser('admin');
    $target = User::factory()->create();

    foreach (['admin', 'super_admin'] as $slug) {
        $this->actingAs($admin)->withSession(confirmedPassword())
            ->post(route('admin.staff.roles.assign', $target), ['role_id' => role($slug)->id, 'reason' => 'Deneme gerekçesi'])
            ->assertSessionHasErrors('role');
    }

    expect($target->fresh()->isStaff())->toBeFalse();
});

it('ADMIN-6: doğrulanmamış hesaba personel rolü atanamaz', function (): void {
    $super = staffUser('super_admin');
    $target = User::factory()->unverified()->create();

    $this->actingAs($super)->withSession(confirmedPassword())
        ->post(route('admin.staff.roles.assign', $target), ['role_id' => role('moderator')->id, 'reason' => 'Deneme gerekçesi'])
        ->assertSessionHasErrors('role');
});

it('FR-ADM-004: rol atama parola onayı ister', function (): void {
    $admin = staffUser('admin');

    $this->actingAs($admin)->post(route('admin.staff.roles.assign', User::factory()->create()), ['role_id' => role('moderator')->id, 'reason' => 'Deneme gerekçesi'])
        ->assertRedirect(route('password.confirm'));
});

it('ADMIN-4.2: son süper adminin rolü kaldırılamaz ve hesabı askıya alınamaz', function (): void {
    $super = staffUser('super_admin');

    $this->actingAs($super)->withSession(confirmedPassword())
        ->post(route('admin.staff.roles.revoke', [$super, role('super_admin')]), ['reason' => 'Kendi rolümü kaldırıyorum'])
        ->assertSessionHasErrors('role');

    $second = staffUser('super_admin');
    $this->actingAs($second)->withSession(confirmedPassword())
        ->post(route('admin.users.status', $super), ['action' => 'suspend', 'reason' => 'Deneme askısı'])
        ->assertSessionHasNoErrors();
    expect($super->fresh()->isSuspended())->toBeTrue();

    // İkinci süper admin askıdakinin rolünü kaldırabilir (iki rol satırı var), ardından kendisi son kalır.
    $this->actingAs($second)->withSession(confirmedPassword())
        ->post(route('admin.staff.roles.revoke', [$super, role('super_admin')]), ['reason' => 'Askıdaki hesabın rolü'])
        ->assertSessionHasNoErrors();
    $this->actingAs($second)->withSession(confirmedPassword())
        ->post(route('admin.staff.roles.revoke', [$second, role('super_admin')]), ['reason' => 'Kendi rolümü kaldırıyorum'])
        ->assertSessionHasErrors('role');
});

it('ADMIN-5.3: son personel rolü kaldırılınca branş üyelikleri ve oturumlar silinir', function (): void {
    $admin = staffUser('admin');
    $agent = staffUser('support_agent');
    DB::table('branch_members')->insert(['branch_id' => Branch::query()->where('slug', 'destek-genel')->value('id'), 'user_id' => $agent->id, 'created_at' => now()]);
    DB::table('sessions')->insert(['id' => 'ajan-oturum', 'user_id' => $agent->id, 'payload' => '', 'last_activity' => time()]);

    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.staff.roles.revoke', [$agent, role('support_agent')]), ['reason' => 'Ekipten ayrıldı'])
        ->assertSessionHasNoErrors();

    expect(DB::table('branch_members')->where('user_id', $agent->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $agent->id)->count())->toBe(0)
        ->and($agent->fresh()->isStaff())->toBeFalse();
});

// ---- Askı ------------------------------------------------------------------

it('FR-ADM-006: admin oyuncuyu askıya alır, gerekçe zorunlu ve audit’e yazılır', function (): void {
    $admin = staffUser('admin');
    $player = User::factory()->create();

    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.users.status', $player), ['action' => 'suspend', 'reason' => ''])
        ->assertSessionHasErrors('reason');

    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.users.status', $player), ['action' => 'suspend', 'reason' => 'Hile kullanımı', 'until' => now()->addDays(3)->format('Y-m-d H:i')])
        ->assertSessionHasNoErrors();

    expect($player->fresh()->isSuspended())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'users.suspended')->value('reason'))->toBe('Hile kullanımı');
});

it('ROUTES-6.2: personeli yalnızca daha yüksek öncelikli kişi askıya alabilir', function (): void {
    $gm = staffUser('game_master');
    $otherGm = staffUser('game_master');
    $admin = staffUser('admin');

    // Eşit öncelik (GM → GM) reddedilir.
    $this->actingAs($gm)->withSession(confirmedPassword())
        ->post(route('admin.users.status', $otherGm), ['action' => 'suspend', 'reason' => 'Deneme askısı'])->assertSessionHasErrors('status');

    $otherAdmin = staffUser('admin');
    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.users.status', $otherAdmin), ['action' => 'suspend', 'reason' => 'Deneme askısı'])->assertSessionHasErrors('status');

    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.users.status', $gm), ['action' => 'suspend', 'reason' => 'Deneme askısı'])->assertSessionHasNoErrors();
});

it('FR-ADM-005: users.read_pii olmadan e-posta maskeli gösterilir', function (): void {
    $gm = staffUser('game_master');
    $player = User::factory()->create(['email' => 'gizli.adres@example.com']);

    $canPii = $gm->can('users.read_pii');
    $response = $this->actingAs($gm)->get(route('admin.users.show', $player))->assertOk();
    $canPii ? $response->assertSee('gizli.adres@example.com') : $response->assertDontSee('gizli.adres@example.com')->assertSee('g***@e***.com');

    $this->actingAs(staffUser('admin'))->get(route('admin.users.show', $player))->assertSee('gizli.adres@example.com');
});

// ---- Ayarlar ---------------------------------------------------------------

it('FR-ADM-009: ayar değişikliği kaydedilir ve audit’e yazılır; süper-only anahtarlar admin için kilitli', function (): void {
    $admin = staffUser('admin');

    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.settings.update', 'maintenance'), ['maintenance__banner_enabled' => '1', 'maintenance__banner_text' => 'Bakım 22:00', 'maintenance__full' => '0'])
        ->assertSessionHasNoErrors();

    expect(app(SettingsRepository::class)->get('maintenance.banner_text'))->toBe('Bakım 22:00')
        ->and(AuditLog::query()->where('action', 'settings.updated')->count())->toBe(2);

    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.settings.update', 'security'), ['security__new_login_alerts' => '0'])
        ->assertSessionHasNoErrors();
    expect(app(SettingsRepository::class)->get('security.new_login_alerts'))->toBeTrue();

    $super = staffUser('super_admin');
    $this->actingAs($super)->withSession(confirmedPassword())
        ->post(route('admin.settings.update', 'security'), ['security__new_login_alerts' => '0'])->assertSessionHasNoErrors();
    expect(app(SettingsRepository::class)->get('security.new_login_alerts'))->toBeFalse();
});

// ---- Branşlar --------------------------------------------------------------

it('ADMIN-5.3: branş lideri yalnızca kendi branşına lider olmayan personel ekler', function (): void {
    $leader = staffUser('support_agent');
    $branch = Branch::query()->where('slug', 'destek-genel')->firstOrFail();
    $other = Branch::query()->where('slug', 'destek-teknik')->firstOrFail();
    DB::table('branch_members')->insert(['branch_id' => $branch->id, 'user_id' => $leader->id, 'is_lead' => true, 'created_at' => now()]);
    $leader->flushAuthz();

    $newAgent = staffUser('support_agent');
    $player = User::factory()->create();

    $this->actingAs($leader)->post(route('admin.branches.members.store', $branch), ['user' => $newAgent->username])->assertSessionHasNoErrors();
    $this->actingAs($leader)->post(route('admin.branches.members.store', $branch), ['user' => $player->username])->assertSessionHasErrors('user');
    $this->actingAs($leader)->post(route('admin.branches.members.store', $other), ['user' => $newAgent->username])->assertForbidden();
    $this->actingAs($leader)->post(route('admin.branches.members.store', $branch), ['user' => staffUser('support_agent')->username, 'is_lead' => '1'])->assertForbidden();

    $newAgent->flushAuthz();
    expect($newAgent->activeBranchIds())->toBe([$branch->id]);
});

// ---- Komutlar ve oturum ----------------------------------------------------

it('ADMIN-4.3: mrp:make-super-admin mevcut hesaba break-glass ile rol verir', function (): void {
    staffUser();
    $user = User::factory()->unverified()->create(['email' => 'kurucu@example.com']);

    $this->artisan('mrp:make-super-admin', ['email' => 'kurucu@example.com'])->assertSuccessful();

    $fresh = User::query()->findOrFail($user->id);
    expect($fresh->isSuperAdmin())->toBeTrue()
        ->and($fresh->hasVerifiedEmail())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'roles.super_admin_break_glass')->value('actor_type'))->toBe('system');
});

it('ADMIN-4.3: mrp:make-super-admin yeni hesap oluşturur (parola interaktif)', function (): void {
    staffUser();

    $this->artisan('mrp:make-super-admin', ['email' => 'yeni.kurucu@example.com'])
        ->expectsQuestion('Yeni hesap için kullanıcı adı', 'kurucu')
        ->expectsQuestion('Parola (en az 10 karakter)', 'Turkuaz-Meridyen-91')
        ->expectsQuestion('Parola (tekrar)', 'Turkuaz-Meridyen-91')
        ->assertSuccessful();

    expect(User::query()->where('email', 'yeni.kurucu@example.com')->sole()->isSuperAdmin())->toBeTrue();
});

it('ADMIN-6: mrp:expire-roles süresi dolan rolü kaldırır ve audit yazar', function (): void {
    $mod = staffUser('moderator');
    DB::table('user_roles')->where('user_id', $mod->id)->update(['expires_at' => now()->subMinute()]);

    $this->artisan('mrp:expire-roles')->assertSuccessful();

    expect(DB::table('user_roles')->where('user_id', $mod->id)->count())->toBe(0)
        ->and(AuditLog::query()->where('action', 'roles.expired')->value('actor_type'))->toBe('scheduler');
});

it('FR-ADM-003: 30 dk hareketsiz admin oturumu parola onayına yönlendirilir', function (): void {
    $admin = staffUser('admin');

    $this->actingAs($admin)->withSession(['admin.last_seen' => time() - 1801])
        ->get(route('admin.dashboard'))->assertRedirect(route('password.confirm'));

    $this->actingAs($admin)->withSession(['admin.last_seen' => time() - 60])
        ->get(route('admin.dashboard'))->assertOk();
});

it('DB-11.4: admin görüntülemeleri aktivite loguna yazılır', function (): void {
    $admin = staffUser('admin');
    $player = User::factory()->create();

    $this->actingAs($admin)->get(route('admin.users.show', $player))->assertOk();

    expect(AdminActivityLog::query()->where(['user_id' => $admin->id, 'route_name' => 'admin.users.show', 'target_type' => 'user', 'target_id' => $player->id])->exists())->toBeTrue();
});

it('ADMIN-3.6: finans yöneticisi audit logda yalnızca wallet/payments/refunds eylemlerini görür', function (): void {
    $fin = staffUser('finance_manager');
    $visible = app(AuditLogger::class)->record(AuditAction::SettingsUpdated, null, ['key' => 'x'], null, null, 'system');

    $this->actingAs($fin)->get(route('admin.audit-logs.index'))->assertOk()->assertDontSee('settings.updated');
    $this->actingAs($fin)->get(route('admin.audit-logs.show', $visible->ulid))->assertNotFound();
    $this->actingAs(staffUser('admin'))->get(route('admin.audit-logs.index'))->assertSee('settings.updated');
});

it('FR-AUTH-017: personel 2FA’yı kapatamaz; oyuncu kapatabilir', function (): void {
    $staff = staffUser('moderator');

    $this->actingAs($staff)->withSession(confirmedPassword())->delete('/user/two-factor-authentication')->assertSessionHasErrors('two_factor');
    expect($staff->fresh()->hasTwoFactorEnabled())->toBeTrue();

    $player = staffUser(); // rolsüz ama 2FA'lı
    $this->actingAs($player)->withSession(confirmedPassword())->delete('/user/two-factor-authentication');
    expect($player->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('FR-AUTH-016: 2FA kurulum sayfası QR’ı data URI olarak gösterir', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(confirmedPassword())->post('/user/two-factor-authentication')->assertRedirect();

    $this->actingAs($user)->get(route('panel.security.two-factor'))->assertOk()->assertSee('data:image/svg+xml;base64,', false);
});
