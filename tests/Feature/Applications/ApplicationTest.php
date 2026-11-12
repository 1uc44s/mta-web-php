<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Models\Application;
use App\Models\ApplicationType;
use App\Models\Branch;
use App\Models\GameAccountLink;
use App\Models\MtaAccount;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    staffUser(); // rolleri ve branşları seed et
    app(SettingsRepository::class)->set('features.applications', true);
});

/** @param  array<string, mixed>  $overrides */
function appType(string $slug, array $overrides = []): ApplicationType
{
    $branch = Branch::query()->create(['slug' => 'fraksiyon-'.$slug, 'name' => 'Fraksiyon '.$slug, 'type' => 'faction', 'is_active' => true]);

    return ApplicationType::query()->create(array_merge([
        'slug' => $slug, 'name' => strtoupper($slug).' başvurusu', 'kind' => 'faction', 'form_key' => 'faction_default',
        'branch_id' => $branch->id, 'is_open' => true, 'requires_linked_account' => false, 'cooldown_days' => 14,
    ], $overrides));
}

function linkedApplicant(): User
{
    $user = User::factory()->create();
    $user->forceFill(['created_at' => now()->subDays(60)])->save();
    $server = bridgeCredential()['server'];
    $account = MtaAccount::query()->create(['mta_server_id' => $server->id, 'mta_account_ref' => 'r'.$user->id, 'account_name' => 'oyuncu'.$user->id]);
    GameAccountLink::query()->create(['user_id' => $user->id, 'mta_account_id' => $account->id, 'status' => 'active', 'verification_method' => 'ingame_code', 'linked_at' => now()]);

    return $user;
}

/** @return array<string, mixed> */
function validAnswers(): array
{
    return [
        'character_story' => str_repeat('Karakterim Los Santos limanında büyüdü ve düzen için çalışmak istiyor. ', 4),
        'roleplay_experience' => 'İki yıldır farklı sunucularda roleplay yapıyorum, polis rolünde deneyimim var.',
        'weekly_hours' => 12,
        'scenario' => str_repeat('Önce sakin şekilde uyarır, kayıt altına alır ve amirime bildiririm. ', 2),
    ];
}

function submitApplication(User $user, ApplicationType $type): Application
{
    test()->actingAs($user)->post(route('panel.applications.store', $type->slug), ['action' => 'submit', 'answers' => validAnswers()])->assertRedirect();

    return Application::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
}

function factionReviewer(ApplicationType $type): User
{
    $staff = staffUser('faction_manager');
    DB::table('branch_members')->insert(['branch_id' => $type->branch_id, 'user_id' => $staff->id, 'created_at' => now()]);
    $staff->flushAuthz();

    return $staff;
}

it('FR-APP-002/003: taslak kaydedilir, gönderimde tam doğrulama yapılır ve soru etiketi kopyalanır', function (): void {
    $type = appType('lspd');
    $user = linkedApplicant();

    $this->actingAs($user)->post(route('panel.applications.store', $type->slug), ['action' => 'save_draft', 'answers' => ['weekly_hours' => 5]])->assertRedirect();
    $app = Application::query()->firstOrFail();
    expect($app->status)->toBe('draft');

    $this->actingAs($user)->post(route('panel.applications.update', $app), ['action' => 'submit', 'answers' => ['character_story' => 'kısa']])
        ->assertSessionHasErrors(['answers.character_story', 'answers.scenario']);

    $this->actingAs($user)->post(route('panel.applications.update', $app), ['action' => 'submit', 'answers' => validAnswers()])->assertRedirect();

    expect($app->fresh()->status)->toBe('submitted')
        ->and($app->answers()->where('field_key', 'weekly_hours')->value('question_label'))->toContain('Haftada')
        ->and(DB::table('application_status_history')->where('application_id', $app->id)->orderBy('id')->pluck('to_status')->all())->toBe(['draft', 'submitted']);
});

it('FR-APP-004: tür başına tek açık başvuru (DB unique) ve ret sonrası bekleme süresi', function (): void {
    $type = appType('lspd');
    $user = linkedApplicant();
    $app = submitApplication($user, $type);

    $this->actingAs($user)->post(route('panel.applications.store', $type->slug), ['action' => 'submit', 'answers' => validAnswers()])->assertSessionHasErrors('type');
    expect(fn () => Application::query()->create(['user_id' => $user->id, 'application_type_id' => $type->id, 'status' => 'draft']))
        ->toThrow(UniqueConstraintViolationException::class);

    $this->actingAs(factionReviewer($type))->post(route('admin.applications.status', $app), ['status' => 'rejected', 'note_public' => 'Hikâye yetersiz.'])->assertRedirect();

    expect(DB::table('audit_logs')->where('action', AuditAction::ApplicationsDecided->value)->count())->toBe(1);
    $this->actingAs($user)->get(route('panel.applications.create', $type->slug))->assertOk()->assertSee('14 gün beklemelisin');

    $this->travel(15)->days();
    $this->actingAs($user)->get(route('panel.applications.create', $type->slug))->assertOk()->assertDontSee('14 gün beklemelisin');
});

it('FR-APP-001: bağlı hesap ve hesap yaşı şartları uygulanır', function (): void {
    $type = appType('lspd', ['requires_linked_account' => true, 'min_account_age_days' => 30]);

    $this->actingAs(User::factory()->create())->post(route('panel.applications.store', $type->slug), ['action' => 'submit', 'answers' => validAnswers()])->assertSessionHasErrors('type');

    $young = linkedApplicant();
    $young->forceFill(['created_at' => now()->subDays(5)])->save();
    $this->actingAs($young)->post(route('panel.applications.store', $type->slug), ['action' => 'submit', 'answers' => validAnswers()])->assertSessionHasErrors('type');

    submitApplication(linkedApplicant(), $type);
    expect(Application::query()->where('status', 'submitted')->count())->toBe(1);
});

it('FR-APP-005: fraksiyon yöneticisi başka fraksiyonun başvurusuna erişemez', function (): void {
    $lspd = appType('lspd');
    $ems = appType('ems');
    $lspdApp = submitApplication(linkedApplicant(), $lspd);
    $emsApp = submitApplication(linkedApplicant(), $ems);
    $manager = factionReviewer($lspd);

    $this->actingAs($manager)->get(route('admin.applications.index', ['filter' => 'all']))->assertOk()
        ->assertSee($lspdApp->user->username)->assertDontSee($emsApp->user->username);
    $this->actingAs($manager)->get(route('admin.applications.show', $lspdApp))->assertOk();
    $this->actingAs($manager)->get(route('admin.applications.show', $emsApp))->assertNotFound();
    $this->actingAs($manager)->post(route('admin.applications.status', $emsApp), ['status' => 'approved'])->assertNotFound();

    expect($emsApp->fresh()->status)->toBe('submitted');
});

it('FR-APP-003/006: bilgi isteme public not ister; oyuncu yanıtı in_review yapar; iç mesaj ve iç not oyuncuya görünmez', function (): void {
    $type = appType('lspd');
    $user = linkedApplicant();
    $app = submitApplication($user, $type);
    $reviewer = factionReviewer($type);

    $this->actingAs($reviewer)->post(route('admin.applications.status', $app), ['status' => 'needs_info'])->assertSessionHasErrors('note_public');
    $this->actingAs($reviewer)->post(route('admin.applications.status', $app), ['status' => 'needs_info', 'note_public' => 'Discord adını ekler misin?', 'note_internal' => 'GIZLI-IC-NOT'])->assertRedirect();
    $this->actingAs($reviewer)->post(route('admin.applications.messages.store', $app), ['body' => 'GIZLI-IC-MESAJ', 'visibility' => 'internal'])->assertRedirect();

    $this->actingAs($user)->get(route('panel.applications.show', $app))->assertOk()
        ->assertSee('Discord adını ekler misin?')->assertDontSee('GIZLI-IC-NOT')->assertDontSee('GIZLI-IC-MESAJ');

    $this->actingAs($user)->post(route('panel.applications.messages.store', $app), ['body' => 'kaan#1234'])->assertRedirect();
    expect($app->fresh()->status)->toBe('in_review')->and($user->notifications()->count())->toBeGreaterThanOrEqual(1);

    $this->actingAs($reviewer)->post(route('admin.applications.status', $app), ['status' => 'approved', 'note_public' => 'Hoş geldin!'])->assertRedirect();
    expect($app->fresh()->status)->toBe('approved')->and($app->fresh()->decided_by)->toBe($reviewer->id);
});

it('FR-APP-003: oyuncu gönderilmiş başvuruyu iptal edebilir; sonuçlanmışı edemez; başkasınınki 404', function (): void {
    $type = appType('lspd');
    $user = linkedApplicant();
    $app = submitApplication($user, $type);

    $this->actingAs(User::factory()->create())->get(route('panel.applications.show', $app))->assertNotFound();
    $this->actingAs($user)->post(route('panel.applications.cancel', $app))->assertRedirect();
    expect($app->fresh()->status)->toBe('cancelled');
    $this->actingAs($user)->post(route('panel.applications.cancel', $app))->assertSessionHasErrors('status');
});

it('DATABASE §9.5: başvuru süreç geçmişi append-only', function (): void {
    $app = submitApplication(linkedApplicant(), appType('lspd'));

    expect(fn () => DB::table('application_status_history')->where('application_id', $app->id)->update(['to_status' => 'approved']))->toThrow(QueryException::class)
        ->and(fn () => DB::table('application_status_history')->where('application_id', $app->id)->delete())->toThrow(QueryException::class);
});

it('ROUTES §6.5: başvuru türleri CMS kaynağıyla yönetilir', function (): void {
    $this->actingAs(staffUser('admin'))->get(route('admin.application-types.index'))->assertOk();
    $this->actingAs(staffUser('faction_manager'))->get(route('admin.application-types.index'))->assertForbidden();
});
