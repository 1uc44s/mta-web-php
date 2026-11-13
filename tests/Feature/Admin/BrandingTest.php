<?php

declare(strict_types=1);

use App\Services\Messaging\DefaultTemplates;
use App\Services\Settings\SettingsRepository;
use App\Support\Brand;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/* Marka yönetimi — docs/DESIGN.md §2, ROUTES §6 (admin.branding*), ADMIN §7 (site.logo_path, site.icon_set). */

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function brandingPost(array $extra = []): array
{
    return $extra + ['name' => 'Vinewood Roleplay', 'short_name' => 'Vinewood', 'tagline' => 'Işıklar senin için yanıyor.'];
}

it('süper admin adı, logoyu ve sekme ikonunu değiştirir; görseller yeniden kodlanır ve audit yazılır', function (): void {
    $admin = staffUser('super_admin');

    $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.branding.update'), brandingPost([
        'logo' => UploadedFile::fake()->image('logo.png', 900, 300),
        'icon' => UploadedFile::fake()->image('icon.png', 256, 256),
    ]))->assertRedirect(route('admin.branding'))->assertSessionHasNoErrors();

    $settings = app(SettingsRepository::class);
    $logo = (string) $settings->get('site.logo_path');
    $icons = (string) $settings->get('site.icon_set');

    expect($settings->get('site.name'))->toBe('Vinewood Roleplay')
        ->and($logo)->toMatch('#^branding/logo-[0-9a-z]{26}\.webp$#')
        ->and($icons)->toMatch('#^branding/icons-[0-9a-z]{26}$#');
    Storage::disk('public')->assertExists($logo);
    foreach (Brand::ICON_SIZES as $size) {
        Storage::disk('public')->assertExists($icons.'/icon-'.$size.'.png');
        $info = getimagesizefromstring((string) Storage::disk('public')->get($icons.'/icon-'.$size.'.png'));
        expect([$info[0] ?? 0, $info[1] ?? 0])->toBe([$size, $size]);
    }
    $logoInfo = getimagesizefromstring((string) Storage::disk('public')->get($logo));
    expect($logoInfo[0] ?? 999)->toBeLessThanOrEqual(640)->and($logoInfo[1] ?? 999)->toBeLessThanOrEqual(200);

    expect(DB::table('audit_logs')->where('action', 'settings.updated')->count())->toBeGreaterThanOrEqual(4);
});

it('yeni ad sekme başlığında, menüde, manifestte ve e-posta imzasında; ikon ve logo sayfada kullanılır', function (): void {
    $this->seed(DatabaseSeeder::class);
    $admin = staffUser('super_admin');
    $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.branding.update'), brandingPost([
        'logo' => UploadedFile::fake()->image('logo.png', 600, 200),
        'icon' => UploadedFile::fake()->image('icon.png', 128, 128),
    ]))->assertSessionHasNoErrors();
    auth()->logout();

    $this->get('/')->assertOk()
        ->assertSee('<title>Vinewood Roleplay', false)
        ->assertSee((string) Brand::logoUrl(), false)
        ->assertSee(Brand::iconUrl(32), false)
        ->assertSee('Vinewood, MTA:SA', false)       // seed içeriğindeki {site_short} doldurulur
        ->assertDontSee('{site_short}', false)
        ->assertDontSee('Meridyen', false);

    $this->get(route('pwa.manifest'))->assertOk()
        ->assertJsonPath('name', 'Vinewood Roleplay')
        ->assertJsonPath('short_name', 'Vinewood')
        ->assertJsonPath('icons.0.src', Brand::iconUrl(192));

    $mail = DefaultTemplates::render('auth.verify_email', ['user.username' => 'oyuncu', 'link.verify' => 'https://example.test/v', 'expires_minutes' => 60]);
    expect($mail['salutation'])->toBe('Vinewood Roleplay ekibi');
});

it('SVG, görsel olmayan dosya ve küçük ikon reddedilir; hiçbir dosya yazılmaz', function (): void {
    $admin = staffUser('super_admin');
    $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.branding.update'), brandingPost(['logo' => $svg]))
        ->assertSessionHasErrors('logo');
    $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.branding.update'), brandingPost(['icon' => UploadedFile::fake()->create('icon.png', 10, 'application/x-php')]))
        ->assertSessionHasErrors('icon');
    $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.branding.update'), brandingPost(['icon' => UploadedFile::fake()->image('icon.png', 32, 32)]))
        ->assertSessionHasErrors('icon');

    expect(Storage::disk('public')->allFiles())->toBe([])
        ->and(app(SettingsRepository::class)->get('site.name'))->not->toBe('Vinewood Roleplay');
});

it('logo kaldırılınca dosya silinir ve varsayılana dönülür; eski görsel yeni yüklemede silinir', function (): void {
    $admin = staffUser('super_admin');
    $upload = fn () => $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.branding.update'), brandingPost(['logo' => UploadedFile::fake()->image('logo.png', 400, 120)]));

    $upload();
    $first = (string) app(SettingsRepository::class)->get('site.logo_path');
    $upload();
    $second = (string) app(SettingsRepository::class)->get('site.logo_path');
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.branding.remove', 'logo'))->assertRedirect(route('admin.branding'));
    Storage::disk('public')->assertMissing($second);
    expect(Brand::logoUrl())->toBeNull();
});

it('yetki: parola onayı ve settings.update gerekir; genel ayar formu logo/ikon anahtarlarını göstermez ve kabul etmez', function (): void {
    $admin = staffUser('super_admin');
    $this->actingAs($admin)->post(route('admin.branding.update'), brandingPost())->assertRedirect(route('password.confirm'));
    $this->actingAs(staffUser('support_agent'))->withSession(confirmedPassword())->post(route('admin.branding.update'), brandingPost())->assertForbidden();

    $this->actingAs($admin)->get(route('admin.settings.edit', 'site'))->assertOk()->assertDontSee('site__logo_path')->assertDontSee('site__icon_set');
    $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.settings.update', 'site'), [
        'site__name' => 'Ayar Formu', 'site__logo_path' => '../../.env',
    ]);
    expect(app(SettingsRepository::class)->get('site.logo_path'))->toBeNull()
        ->and(app(SettingsRepository::class)->get('site.name'))->toBe('Ayar Formu');

    $this->actingAs($admin)->get(route('admin.branding'))->assertOk()->assertSee('Ayar Formu');
});
