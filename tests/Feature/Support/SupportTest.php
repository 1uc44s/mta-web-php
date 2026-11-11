<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\SupportAttachment;
use App\Models\SupportCategory;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportActivity;
use App\Notifications\TemplatedMailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/* Faz 6 — docs/SRS.md FR-SUP-001..011, SEC-027; ROADMAP Faz 6 çıkış kriterleri (E2E-8/9/10, iç not 3 katman, IDOR, upload bypass). */

uses(RefreshDatabase::class);

beforeEach(function (): void {
    staffUser(); // roller, branşlar, destek kategorileri
    Storage::fake('local');
    Notification::fake();
});

function category(string $slug = 'hesap-ve-giris'): SupportCategory
{
    return SupportCategory::query()->where('slug', $slug)->firstOrFail();
}

function openTicket(User $player, array $overrides = []): SupportTicket
{
    test()->actingAs($player)->post(route('panel.support.store'), [
        'category_id' => category()->id, 'subject' => 'Giriş yapamıyorum', 'body' => 'Parolamı sıfırladım ama hâlâ giriş yapamıyorum.',
        'idempotency_key' => (string) Str::uuid(), ...$overrides,
    ])->assertSessionHasNoErrors();

    return SupportTicket::query()->latest('id')->firstOrFail();
}

/** Destek branşına üye destek yetkilisi. */
function agent(string $branch = 'destek-genel'): User
{
    $user = staffUser('support_agent');
    DB::table('branch_members')->insert(['branch_id' => Branch::query()->where('slug', $branch)->value('id'), 'user_id' => $user->id, 'created_at' => now()]);
    $user->flushAuthz();

    return $user;
}

// ---- Oluşturma ---------------------------------------------------------------

it('E2E-8 / FR-SUP-001/004: oyuncu ekli talep açar; numara, branş ve olay oluşur', function (): void {
    $player = User::factory()->create();
    $ticket = openTicket($player, ['attachments' => [UploadedFile::fake()->image('ekran.png', 800, 600)]]);

    expect($ticket->number)->toBe('T-000001')
        ->and($ticket->branch_id)->toBe(category()->branch_id)
        ->and($ticket->status)->toBe('open')
        ->and($ticket->events()->where('type', 'created')->exists())->toBeTrue();

    $attachment = SupportAttachment::query()->sole();
    expect($attachment->mime)->toBe('image/webp')->and($attachment->path)->toEndWith('.webp');
    Storage::disk('local')->assertExists($attachment->path);
    expect(openTicket($player)->number)->toBe('T-000002');
});

it('FR-SUP-003: aynı idempotency anahtarıyla ikinci gönderim aynı talebe gider', function (): void {
    $player = User::factory()->create();
    $key = (string) Str::uuid();
    openTicket($player, ['idempotency_key' => $key]);
    openTicket($player, ['idempotency_key' => $key]);

    expect(SupportTicket::query()->count())->toBe(1);
});

it('FR-SUP-001: doğrulama kuralları', function (array $overrides, string $field): void {
    $this->actingAs(User::factory()->create())->post(route('panel.support.store'), [
        'category_id' => category()->id, 'subject' => 'Geçerli konu', 'body' => 'Yeterince uzun bir açıklama metni burada.',
        'idempotency_key' => (string) Str::uuid(), ...$overrides,
    ])->assertSessionHasErrors($field);
})->with([
    'kısa konu' => [['subject' => 'abc'], 'subject'],
    'kısa açıklama' => [['body' => 'kısa'], 'body'],
    'geçersiz anahtar' => [['idempotency_key' => 'x'], 'idempotency_key'],
    '4 ek' => [['attachments' => array_fill(0, 4, UploadedFile::fake()->create('a.txt', 1, 'text/plain'))], 'attachments'],
]);

it('FR-SUP-001: e-postası doğrulanmamış oyuncu destek alanına giremez', function (): void {
    $this->actingAs(User::factory()->unverified()->create())->get(route('panel.support.index'))->assertRedirect(route('verification.notice'));
});

it('SEC-027: dosya yükleme bypass denemeleri reddedilir', function (UploadedFile $file): void {
    $this->actingAs(User::factory()->create())->post(route('panel.support.store'), [
        'category_id' => category()->id, 'subject' => 'Ek denemesi', 'body' => 'Bu bir ek doğrulama denemesidir, lütfen yok sayın.',
        'idempotency_key' => (string) Str::uuid(), 'attachments' => [$file],
    ])->assertSessionHasErrors();

    expect(SupportTicket::query()->count())->toBe(0);
})->with([
    'php içeriği .jpg uzantısıyla (sahte MIME)' => [UploadedFile::fake()->createWithContent('resim.jpg', '<?php system($_GET["c"]); ?>')],
    'çift uzantı' => [UploadedFile::fake()->createWithContent('resim.php.png', '<?php echo 1; ?>')],
    'svg' => [UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>')],
    'exe' => [UploadedFile::fake()->create('kurulum.exe', 10, 'application/x-msdownload')],
    'sahte pdf' => [UploadedFile::fake()->createWithContent('belge.pdf', 'aslında pdf değil')],
    '5 MB üstü' => [UploadedFile::fake()->create('buyuk.pdf', 6000, 'application/pdf')],
]);

it('SEC-027: polyglot görsel yeniden kodlanır; PHP yükü saklanmaz', function (): void {
    $player = User::factory()->create();
    $png = UploadedFile::fake()->image('a.png', 50, 50);
    $polyglot = UploadedFile::fake()->createWithContent('poly.png', file_get_contents($png->getRealPath()).'<?php system("id"); ?>');

    openTicket($player, ['attachments' => [$polyglot]]);

    $stored = (string) Storage::disk('local')->get(SupportAttachment::query()->sole()->path);
    expect($stored)->not->toContain('<?php')->and(substr($stored, 0, 4))->toBe('RIFF');
});

// ---- Sahiplik ve iç not izolasyonu ---------------------------------------

it('FR-SUP-006 / IDOR: başkasının talebi ve eki 404 döner', function (): void {
    $owner = User::factory()->create();
    $ticket = openTicket($owner, ['attachments' => [UploadedFile::fake()->createWithContent('log.txt', "satır 1\nsatır 2")]]);
    $other = User::factory()->create();
    $attachment = SupportAttachment::query()->sole();

    $this->actingAs($other)->get(route('panel.support.show', $ticket))->assertNotFound();
    $this->actingAs($other)->post(route('panel.support.messages.store', $ticket), ['body' => 'Merhaba'])->assertNotFound();
    $this->actingAs($other)->get(route('panel.support.attachments.show', $attachment))->assertNotFound();
    $this->actingAs($owner)->get(route('panel.support.attachments.show', $attachment))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('FR-SUP-006: iç not oyuncuya üç katmanda da sızmaz (scope, uç, görünüm) ve bildirim üretmez', function (): void {
    $player = User::factory()->create();
    $ticket = openTicket($player);
    $staff = agent();

    $this->actingAs($staff)->post(route('admin.support.messages.store', $ticket), ['body' => 'GIZLI-IC-NOT: hesap şüpheli', 'visibility' => 'internal'])->assertSessionHasNoErrors();
    $internal = SupportMessage::query()->where('visibility', 'internal')->sole();
    Storage::disk('local')->put('support/ic.txt', 'x');
    $att = SupportAttachment::query()->create([
        'ulid' => strtolower((string) Str::ulid()), 'ticket_id' => $ticket->id, 'message_id' => $internal->id, 'uploaded_by' => $staff->id,
        'disk' => 'local', 'path' => 'support/ic.txt', 'original_name' => 'ic.txt', 'mime' => 'text/plain', 'size_bytes' => 1, 'sha256' => str_repeat('0', 64),
    ]);

    expect($ticket->publicMessages()->pluck('body')->implode(' '))->not->toContain('GIZLI-IC-NOT');                    // 1: scope
    $this->actingAs($player)->get(route('panel.support.attachments.show', $att))->assertNotFound();                   // 2: uç
    $this->actingAs($player)->get(route('panel.support.show', $ticket))->assertOk()->assertDontSee('GIZLI-IC-NOT');   // 3: görünüm
    expect($ticket->fresh()->status)->toBe('open');
    Notification::assertNotSentTo($player, SupportActivity::class);
});

it('FR-SUP-007: yetkili yalnızca kendi branşının kuyruğunu görür; admin hepsini', function (): void {
    $ticket = openTicket(User::factory()->create());
    $technical = agent('destek-teknik');

    $this->actingAs($technical)->get(route('admin.support.index'))->assertOk()->assertDontSee($ticket->number);
    $this->actingAs($technical)->get(route('admin.support.show', $ticket))->assertNotFound();
    $this->actingAs(agent())->get(route('admin.support.index'))->assertSee($ticket->number);
    $this->actingAs(staffUser('admin'))->get(route('admin.support.show', $ticket))->assertOk();
});

// ---- Yanıtlar, durumlar ve bildirimler ------------------------------------

it('E2E-9 / FR-SUP-005/009/010: yetkili yanıtı (makro değişkenli) → waiting_for_player + panel ve e-posta bildirimi; oyuncu yanıtı → waiting_for_staff', function (): void {
    $player = User::factory()->create();
    $ticket = openTicket($player);
    $staff = agent();

    $this->actingAs($staff)->post(route('admin.support.messages.store', $ticket), ['body' => 'Merhaba {{ player.username }}, {{ ticket.number }} inceleniyor.', 'visibility' => 'public']);

    $ticket->refresh();
    expect($ticket->status)->toBe('waiting_for_player')
        ->and($ticket->assigned_to)->toBe($staff->id)
        ->and($ticket->first_staff_response_at)->not->toBeNull()
        ->and(SupportMessage::query()->where('ticket_id', $ticket->id)->latest('id')->value('body'))->toBe("Merhaba {$player->username}, {$ticket->number} inceleniyor.");
    Notification::assertSentTo($player, SupportActivity::class);
    Notification::assertSentTo($player, TemplatedMailNotification::class, fn ($n) => $n->key === 'support.staff_replied');

    $this->actingAs($player)->post(route('panel.support.messages.store', $ticket), ['body' => 'Teşekkürler, bekliyorum.']);
    expect($ticket->fresh()->status)->toBe('waiting_for_staff');
    Notification::assertSentTo($staff, SupportActivity::class);
});

it('FR-NTF-003: oyuncu destek e-postalarını kapatırsa yalnızca panel bildirimi gider', function (): void {
    $player = User::factory()->create();
    $this->actingAs($player)->post(route('panel.settings.notifications'), ['prefs' => ['announcements' => ['mail' => '1']]]);
    $ticket = openTicket($player);

    $this->actingAs(agent())->post(route('admin.support.messages.store', $ticket), ['body' => 'Yanıt metni', 'visibility' => 'public']);

    Notification::assertSentTo($player, SupportActivity::class);
    Notification::assertNotSentTo($player, TemplatedMailNotification::class);
});

it('E2E-10 / FR-SUP-011: oyuncu kapatır, 7 gün içinde yeniden açma ister, yetkili onaylar; süre geçince reddedilir', function (): void {
    $player = User::factory()->create();
    $ticket = openTicket($player);

    $this->actingAs($player)->post(route('panel.support.close', $ticket));
    expect($ticket->fresh()->status)->toBe('closed');
    $this->actingAs($player)->post(route('panel.support.messages.store', $ticket), ['body' => 'Kapalıya yazıyorum'])->assertSessionHasErrors('body');

    $this->actingAs($player)->post(route('panel.support.reopen-request', $ticket))->assertSessionHasNoErrors();
    $this->actingAs(agent())->post(route('admin.support.reopen-decision', $ticket), ['decision' => 'approve'])->assertSessionHasNoErrors();
    expect($ticket->fresh()->status)->toBe('open');

    $second = openTicket($player);
    $this->actingAs($player)->post(route('panel.support.close', $second));
    $this->travel(8)->days();
    $this->actingAs($player)->post(route('panel.support.reopen-request', $second))->assertSessionHasErrors('reopen');
});

it('FR-SUP-008: escalation branşı değiştirir, atamayı sıfırlar, olay + audit yazar; branş dışı atama reddedilir', function (): void {
    $ticket = openTicket(User::factory()->create());
    $lead = staffUser('admin');
    $technical = agent('destek-teknik');

    $this->actingAs($lead)->post(route('admin.support.assign', $ticket), ['assignee' => $technical->username])->assertSessionHasErrors('assignee');

    $target = Branch::query()->where('slug', 'destek-teknik')->firstOrFail();
    $this->actingAs($lead)->post(route('admin.support.escalate', $ticket), ['branch_id' => $target->id, 'note' => 'Teknik ekip bakmalı'])->assertRedirect(route('admin.support.index'));

    $ticket->refresh();
    expect($ticket->branch_id)->toBe($target->id)->and($ticket->assigned_to)->toBeNull()
        ->and($ticket->events()->where('type', 'escalated')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'support.escalated')->value('reason'))->toBe('Teknik ekip bakmalı');

    $this->actingAs($lead)->post(route('admin.support.assign', $ticket), ['assignee' => $technical->username])->assertSessionHasNoErrors();
    expect($ticket->fresh()->assigned_to)->toBe($technical->id);
});

it('FR-SUP-005: 7 gün yanıtsız → resolved, 7 gün sonra → closed (zamanlayıcı)', function (): void {
    $ticket = openTicket(User::factory()->create());
    $this->actingAs(agent())->post(route('admin.support.messages.store', $ticket), ['body' => 'Yanıt', 'visibility' => 'public']);

    $this->travel(8)->days();
    $this->artisan('mrp:maintenance-tick')->assertSuccessful();
    expect($ticket->fresh()->status)->toBe('resolved');

    $this->travel(8)->days();
    $this->artisan('mrp:maintenance-tick')->assertSuccessful();
    expect($ticket->fresh()->status)->toBe('closed')
        ->and($ticket->events()->whereIn('type', ['auto_resolved', 'auto_closed'])->count())->toBe(2);
});
