<?php

declare(strict_types=1);

use App\Models\GameSystem;
use App\Models\NewsPost;
use Database\Seeders\ContentSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ServerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([ServerSeeder::class, ContentSeeder::class, DemoSeeder::class]);
});

it('FR-PUB-003..014: tüm public sayfalar açılır', function (string $uri): void {
    $this->get($uri)->assertOk()->assertSee('<html lang="tr"', false);
})->with([
    '/', '/about', '/systems', '/systems/ekonomi', '/jobs', '/jobs?class=legal', '/jobs/tamirci', '/factions',
    '/factions?type=illegal', '/factions/emniyet', '/start', '/rules', '/academy', '/academy/baslangic',
    '/academy/baslangic/mta-kurulumu', '/news', '/news/category/duyurular', '/news/web-platformu-yayinda',
    '/events', '/events/acilis-gecesi', '/faq', '/contact', '/privacy', '/kvkk', '/terms', '/cookies',
]);

it('US-PUB-01: ana sayfa CMS bölümlerini ve Oyuna Katıl CTA’sını gösterir', function (): void {
    $this->get('/')
        ->assertSee('senin hikâyen.')
        ->assertSee(__('public.join.cta'))
        ->assertSee('mtasa://127.0.0.1:22003', false)
        ->assertSee('Yaşayan bir şehir için sistemler');
});

it('US-PUB-03: heartbeat yokken durum Çevrimdışı ve oyuncu sayısı yok', function (): void {
    $this->get('/')->assertSee('data-status="offline"', false)->assertDontSee('42 / 300');
});

it('US-PUB-04 KK1: oyunda aktif olmayan sistem public’te görünmez', function (): void {
    GameSystem::query()->where('slug', 'ekonomi')->update(['is_live_in_game' => false]);

    $this->get('/systems')->assertDontSee('Ekonomi ve bankacılık');
    $this->get('/systems/ekonomi')->assertNotFound();
});

it('FR-PUB-011: taslak ve zamanlanmış haber görünmez', function (): void {
    NewsPost::query()->where('slug', 'akademi-acildi')->update(['status' => 'draft']);
    NewsPost::query()->where('slug', 'kurallar-guncellendi')->update(['published_at' => now()->addDay()]);

    $this->get('/news')->assertDontSee('Akademi açıldı')->assertDontSee('Roleplay kuralları güncellendi');
    $this->get('/news/akademi-acildi')->assertNotFound();
});

it('FR-PUB-010: kurallar kod çapalarıyla listelenir', function (): void {
    $this->get('/rules')->assertSee('id="rp-1"', false)->assertSee('RP-1')->assertSee('data-rules-filter', false);
});

it('FR-PUB-016: SEO meta, canonical ve JSON-LD üretilir', function (): void {
    $this->get('/news/web-platformu-yayinda')
        ->assertSee('<link rel="canonical"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('"@type":"Article"', false);
});

it('FR-PUB-015: sitemap yalnızca yayındaki içeriği, robots panel/admin engelini içerir', function (): void {
    NewsPost::query()->where('slug', 'akademi-acildi')->update(['status' => 'draft']);

    $this->get('/sitemap.xml')->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('/news/web-platformu-yayinda', false)
        ->assertDontSee('/news/akademi-acildi', false);

    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /panel/')->assertSee('Disallow: /admin/');
});

it('ROUTES-7.1: public durum API’si standart biçimde döner ve ETag taşır', function (): void {
    $response = $this->getJson('/api/v1/public/server-status')->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'offline')
        ->assertJsonPath('data.connect.port', 22003);

    $this->getJson('/api/v1/public/server-status', ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304);
});

it('RULES-6: görünümlerde satır içi script yalnızca nonce ile bulunur', function (): void {
    $html = $this->get('/')->getContent();

    preg_match_all('/<script(?![^>]*\bsrc=)([^>]*)>/i', (string) $html, $matches);
    foreach ($matches[1] as $attributes) {
        expect($attributes)->toContain('nonce=');
    }
});
