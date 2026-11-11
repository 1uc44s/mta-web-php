<?php

declare(strict_types=1);

use App\Enums\ContentStatus;
use App\Jobs\SendAnnouncementEmails;
use App\Models\AcademyCourse;
use App\Models\AcademyTopic;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\Media;
use App\Models\MessageTemplate;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Rule;
use App\Models\User;
use App\Notifications\TemplatedMailNotification;
use App\Services\Content\MarkdownRenderer;
use App\Services\Messaging\MessageDispatcher;
use App\Services\Settings\SettingsRepository;
use Database\Seeders\ContentSeeder;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/* Faz 5 — docs/CMS.md §2–§7, ROUTES §6.8–6.9, SRS FR-CMS / FR-ADM-011. */

uses(RefreshDatabase::class);

beforeEach(function (): void {
    staffUser();
    (new ContentSeeder)->run();
});

function newsPayload(array $overrides = []): array
{
    return [
        'title' => 'Şehir Meclisi Güncellemesi', 'news_category_id' => NewsCategory::query()->firstOrCreate(['slug' => 'guncelleme'], ['name' => 'Güncelleme'])->id,
        'excerpt' => 'Kısa özet', 'body' => "## Ayrıntılar\n\nMetin.", 'tags' => 'güncelleme, şehir', ...$overrides,
    ];
}

// ---- Yayın iş akışı (E2E-11) ------------------------------------------------

it('E2E-11: yönetici haber oluşturur → taslak public’te görünmez → yayınlar → görünür', function (): void {
    $cm = staffUser('community_manager');

    $this->actingAs($cm)->post(route('admin.content.news.store'), newsPayload())->assertSessionHasNoErrors();
    $post = NewsPost::query()->sole();

    expect($post->slug)->toBe('sehir-meclisi-guncellemesi')
        ->and($post->status)->toBe(ContentStatus::Draft)
        ->and($post->tags()->pluck('slug')->sort()->values()->all())->toBe(['guncelleme', 'sehir']);

    $this->get(route('public.news.show', $post->slug))->assertNotFound();

    $this->actingAs($cm)->post(route('admin.content.news.publish', ['id' => $post->id]))->assertSessionHasNoErrors();

    $this->get(route('public.news.show', $post->slug))->assertOk()->assertSee('Şehir Meclisi Güncellemesi');
    expect(AuditLog::query()->where('action', 'content.published')->exists())->toBeTrue();
});

it('CMS-2: içerik editörü taslak hazırlar ama yayınlayamaz; incelemeye gönderebilir', function (): void {
    $editor = staffUser('content_editor');

    $this->actingAs($editor)->post(route('admin.content.news.store'), newsPayload())->assertSessionHasNoErrors();
    $post = NewsPost::query()->sole();

    $this->actingAs($editor)->post(route('admin.content.news.publish', ['id' => $post->id]))->assertForbidden();
    $this->actingAs($editor)->post(route('admin.content.news.submit-review', ['id' => $post->id]))->assertSessionHasNoErrors();

    expect($post->fresh()->status)->toBe(ContentStatus::InReview);
});

it('FR-CMS-008: ileri tarihli yayın zamanlanır ve komutla yayınlanır', function (): void {
    $cm = staffUser('community_manager');
    $this->actingAs($cm)->post(route('admin.content.news.store'), newsPayload(['published_at' => now()->addHour()->format('Y-m-d H:i')]));
    $post = NewsPost::query()->sole();

    $this->actingAs($cm)->post(route('admin.content.news.publish', ['id' => $post->id]));
    expect($post->fresh()->status)->toBe(ContentStatus::Scheduled);

    $this->travel(2)->hours();
    $this->artisan('content:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->status)->toBe(ContentStatus::Published);
    $this->get(route('public.news.show', $post->slug))->assertOk();
});

it('CMS-2: geçersiz geçiş reddedilir ve arşivlenen içerik public’ten kalkar', function (): void {
    $cm = staffUser('community_manager');
    $this->actingAs($cm)->post(route('admin.content.news.store'), newsPayload());
    $post = NewsPost::query()->sole();

    $this->actingAs($cm)->post(route('admin.content.news.restore', ['id' => $post->id]))->assertSessionHasErrors('status');

    $this->actingAs($cm)->post(route('admin.content.news.publish', ['id' => $post->id]));
    $this->actingAs($cm)->post(route('admin.content.news.archive', ['id' => $post->id]));
    $this->get(route('public.news.show', $post->slug))->assertNotFound();
});

it('CMS-3.1: slug benzersiz, rezerv kelime ve geçersiz biçim reddedilir', function (): void {
    $cm = staffUser('community_manager');
    $this->actingAs($cm)->post(route('admin.content.news.store'), newsPayload());
    $this->actingAs($cm)->post(route('admin.content.news.store'), newsPayload());

    expect(NewsPost::query()->pluck('slug')->sort()->values()->all())->toBe(['sehir-meclisi-guncellemesi', 'sehir-meclisi-guncellemesi-2']);

    $this->actingAs($cm)->post(route('admin.content.news.store'), newsPayload(['slug' => 'create']))->assertSessionHasErrors('slug');
    $this->actingAs($cm)->post(route('admin.content.news.store'), newsPayload(['slug' => 'Büyük Harf']))->assertSessionHasErrors('slug');
});

it('CMS-3: sistem sayfası silinemez', function (): void {
    $admin = staffUser('super_admin');
    $home = Page::query()->where('slug', 'home')->firstOrFail();

    $this->actingAs($admin)->post(route('admin.content.pages.destroy', ['id' => $home->id]))->assertForbidden();
    expect(Page::query()->whereKey($home->id)->exists())->toBeTrue();
});

// ---- Bölümler ve revizyonlar ---------------------------------------------

it('CMS-4.1: bölüm verisi şemaya göre doğrulanır, şema dışı anahtarlar atılır', function (): void {
    $cm = staffUser('community_manager');
    $page = Page::query()->where('slug', 'home')->firstOrFail();
    $section = $page->sections()->where('type', 'cta_banner')->firstOrFail();

    $this->actingAs($cm)->post(route('admin.content.pages.sections.update', [$page, $section]), ['data' => json_encode(['title' => str_repeat('x', 200)]), 'is_visible' => '1'])
        ->assertSessionHasErrors('data');

    $this->actingAs($cm)->post(route('admin.content.pages.sections.update', [$page, $section]), [
        'data' => json_encode(['title' => 'Yeni başlık', 'text' => 'Metin', 'kotu_anahtar' => '<script>']), 'is_visible' => '1',
    ])->assertSessionHasNoErrors();

    expect($section->fresh()->data)->toBe(['title' => 'Yeni başlık', 'text' => 'Metin']);
    $this->get('/')->assertOk()->assertSee('Yeni başlık');
});

it('CMS-2: canlı sayfa düzenlenince revizyon alınır ve geri yüklenebilir', function (): void {
    $cm = staffUser('community_manager');
    $about = Page::query()->where('slug', 'about')->firstOrFail();
    $original = $about->body;

    $this->actingAs($cm)->post(route('admin.content.pages.update', ['id' => $about->id]), [
        'title' => $about->title, 'template' => $about->template, 'body' => 'Değiştirilmiş gövde', 'revision_note' => 'Deneme',
    ])->assertSessionHasNoErrors();

    $revision = DB::table('page_revisions')->where('page_id', $about->id)->sole();
    expect($about->fresh()->body)->toBe('Değiştirilmiş gövde');

    $this->actingAs($cm)->post(route('admin.content.pages.revisions.restore', [$about, $revision->id]))->assertRedirect();
    expect($about->fresh()->body)->toBe($original);
});

// ---- Kurallar ve Akademi -----------------------------------------------------

it('CMS-5.3: kural sürümü notla artırılır; oyuncu onaylar (rule_acknowledgements + consent)', function (): void {
    $cm = staffUser('community_manager');
    $player = User::factory()->create();
    $current = (string) app(SettingsRepository::class)->get('rules.current_version');

    $this->actingAs($cm)->post(route('admin.content.rules.bump-version'), ['version' => '2026.12.0', 'note' => ''])->assertSessionHasErrors('note');
    $this->actingAs($cm)->post(route('admin.content.rules.bump-version'), ['version' => $current, 'note' => 'Aynı sürüm denemesi'])->assertSessionHasErrors('version');
    $this->actingAs($cm)->post(route('admin.content.rules.bump-version'), ['version' => '2026.12.0', 'note' => 'RP-2 bölümü güncellendi'])->assertSessionHasNoErrors();

    $this->actingAs($player)->get('/panel')->assertSee('Kurallar güncellendi')->assertSee('RP-2 bölümü güncellendi');

    $this->actingAs($player)->post(route('panel.rules.acknowledge'))->assertRedirect();
    $this->actingAs($player)->post(route('panel.rules.acknowledge'))->assertRedirect();

    expect(DB::table('rule_acknowledgements')->where(['user_id' => $player->id, 'version' => '2026.12.0'])->count())->toBe(1)
        ->and(Consent::query()->where(['user_id' => $player->id, 'type' => 'rules_ack'])->count())->toBe(1);
    $this->actingAs($player)->get('/panel')->assertDontSee('Kurallar güncellendi');
});

it('CMS-2: kural düzenlemesi rule_revisions kaydı oluşturur', function (): void {
    $cm = staffUser('community_manager');
    $rule = Rule::query()->firstOrFail();

    $this->actingAs($cm)->post(route('admin.content.rules.update', ['id' => $rule->id]), [
        'rule_category_id' => $rule->rule_category_id, 'code' => $rule->code, 'title' => $rule->title, 'body' => 'Yeni kural metni',
    ])->assertSessionHasNoErrors();

    expect(DB::table('rule_revisions')->where('rule_id', $rule->id)->count())->toBe(1);
});

it('CMS-5.2: Akademi konusu idempotent tamamlanır ve ilerleme hesaplanır', function (): void {
    $player = User::factory()->create();
    $topic = AcademyTopic::query()->published()->firstOrFail();
    $course = AcademyCourse::query()->findOrFail($topic->academy_course_id);

    $this->actingAs($player)->post(route('panel.academy.complete', $topic->id))->assertRedirect();
    $this->actingAs($player)->post(route('panel.academy.complete', $topic->id))->assertRedirect();

    expect(DB::table('academy_progress')->where('user_id', $player->id)->count())->toBe(1);
    $this->actingAs($player)->get(route('panel.academy'))->assertOk()->assertSee($course->title)->assertSee('1 / ');
});

// ---- Duyurular ---------------------------------------------------------------

it('FR-ADM-011: yayınlanan duyuru site bandında ve panelde görünür, e-posta işi bir kez kuyruğa girer', function (): void {
    Queue::fake();
    $cm = staffUser('community_manager');

    $this->actingAs($cm)->post(route('admin.announcements.store'), [
        'title' => 'Sunucu bakımı 22:00', 'body' => 'Kısa bakım.', 'priority' => 'important', 'audience' => 'all',
        'channels' => ['public_banner', 'panel', 'email'], 'starts_at' => now()->subMinute()->format('Y-m-d H:i'),
    ])->assertSessionHasNoErrors();
    $a = Announcement::query()->sole();

    $this->get('/')->assertDontSee('Sunucu bakımı 22:00');
    $this->actingAs($cm)->post(route('admin.announcements.publish', ['id' => $a->id]))->assertSessionHasNoErrors();
    $this->actingAs($cm)->post(route('admin.announcements.unpublish', ['id' => $a->id]));
    $this->actingAs($cm)->post(route('admin.announcements.publish', ['id' => $a->id]));

    Queue::assertPushed(SendAnnouncementEmails::class, 1);
    $this->actingAs(User::factory()->create())->get('/')->assertSee('Sunucu bakımı 22:00');
    $this->actingAs(User::factory()->create())->get('/panel')->assertSee('Kısa bakım.');
});

it('FR-ADM-011: duyuru e-postası yalnızca doğrulanmış ve aktif hesaplara gider', function (): void {
    Notification::fake();
    $verified = User::factory()->create();
    $unverified = User::factory()->unverified()->create();
    $a = Announcement::query()->create(['title' => 'Etkinlik', 'body' => 'Cumartesi', 'channels' => ['email'], 'starts_at' => now(), 'status' => ContentStatus::Published, 'published_at' => now()]);

    (new SendAnnouncementEmails($a->id))->handle(app(MessageDispatcher::class), app(MarkdownRenderer::class));

    Notification::assertSentTo($verified, TemplatedMailNotification::class, fn ($n) => $n->key === 'announcements.published');
    Notification::assertNotSentTo($unverified, TemplatedMailNotification::class);
});

// ---- Şablonlar ---------------------------------------------------------------

it('CMS-6: şablon editörü söz dizimini kayıtta doğrular, revizyon ve sürüm tutar', function (): void {
    (new MessageTemplateSeeder)->run();
    $admin = staffUser('admin');
    $template = MessageTemplate::query()->where('key', 'auth.verify_email')->firstOrFail();

    $this->actingAs($admin)->post(route('admin.templates.update', $template), ['subject' => 'Doğrula', 'body' => '{{ user.password }}'])->assertSessionHasErrors('body');
    $this->actingAs($admin)->post(route('admin.templates.update', $template), ['subject' => 'Doğrula', 'body' => "{{ system('id') }}"])->assertSessionHasErrors('body');

    $this->actingAs($admin)->post(route('admin.templates.update', $template), [
        'subject' => 'Hesabını doğrula, {{ user.username }}', 'body' => "Selam {{ user.username }}\n\n[Doğrula]({{ link.verify }})",
    ])->assertSessionHasNoErrors();

    expect($template->fresh()->version)->toBe(2)->and($template->revisions()->count())->toBe(1);

    $this->actingAs($admin)->postJson(route('admin.templates.preview', $template), ['body' => '{{ nope }}'])->assertStatus(422);
    $this->actingAs($admin)->postJson(route('admin.templates.preview', $template), ['subject' => 'K', 'body' => 'Merhaba {{ user.username }}'])
        ->assertOk()->assertJsonPath('success', true);
});

it('CMS-6.5: DB şablonu varsa e-posta ondan render edilir ve gönderim loglanır', function (): void {
    (new MessageTemplateSeeder)->run();
    $user = User::factory()->unverified()->create();

    Notification::fake();
    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, TemplatedMailNotification::class, function (TemplatedMailNotification $n) use ($user): bool {
        $mail = $n->toMail($user);
        expect($mail->subject)->toBe('E-posta adresini doğrula')->and($mail->markdown)->toBe('mail.templated');

        return true;
    });
    expect(DB::table('message_dispatches')->where(['template_key' => 'auth.verify_email', 'status' => 'sent'])->count())->toBe(1);
});

// ---- Medya --------------------------------------------------------------------

it('CMS-3.3: görsel yüklenir, WebP varyantları üretilir; SVG ve alt metinsiz yükleme reddedilir; kullanımdaki görsel silinemez', function (): void {
    Storage::fake('public');
    $cm = staffUser('community_manager');

    $this->actingAs($cm)->post(route('admin.media.store'), ['file' => UploadedFile::fake()->image('harita.png', 2000, 1000), 'alt_text' => 'Şehir haritası'])->assertSessionHasNoErrors();
    $media = Media::query()->sole();

    expect($media->mime)->toBe('image/webp')->and(array_keys($media->variants))->toBe([480, 960, 1600]);
    Storage::disk('public')->assertExists([$media->path, ...array_values($media->variants)]);

    $this->actingAs($cm)->post(route('admin.media.store'), ['file' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'), 'alt_text' => 'x'])->assertSessionHasErrors('file');
    $this->actingAs($cm)->post(route('admin.media.store'), ['file' => UploadedFile::fake()->image('a.png', 100, 100)])->assertSessionHasErrors('alt_text');

    $this->actingAs($cm)->post(route('admin.content.news.store'), newsPayload(['cover_media_id' => $media->id]))->assertSessionHasNoErrors();
    $this->actingAs($cm)->post(route('admin.media.destroy', $media))->assertStatus(409);
});

// ---- Markdown XSS seti -------------------------------------------------------

it('CMS-3.2: Markdown XSS vektörleri sanitize edilir', function (string $payload): void {
    $html = app(MarkdownRenderer::class)->render($payload)->toHtml();

    expect($html)->not->toMatch('/<script|<iframe|<object|<embed|<svg|<style|<details|\son\w+\s*=|href=["\']?\s*(javascript|data|vbscript):|src=["\']?\s*(javascript|data):/i');
})->with([
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    '[x](javascript:alert(1))',
    '[x](JaVaScRiPt:alert(1))',
    '[x](data:text/html;base64,PHNjcmlwdD4=)',
    '![x](javascript:alert(1))',
    '<a href="javascript:alert(1)">x</a>',
    '<iframe src="https://evil.test"></iframe>',
    '<svg onload=alert(1)>',
    '<details open ontoggle=alert(1)>',
    "[x](\x01javascript:alert(1))",
    '<style>body{display:none}</style>',
    '<object data="x.swf"></object>',
    '[a](vbscript:msgbox(1))',
]);
