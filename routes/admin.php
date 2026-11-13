<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\CharacterController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceController;
use App\Http\Controllers\Admin\EconomyController;
use App\Http\Controllers\Admin\GameAccountController;
use App\Http\Controllers\Admin\GameCommandController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\QuizQuestionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SanctionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\UserController;
use App\Services\Content\ContentResourceRegistry;
use Illuminate\Support\Facades\Route;

/*
 * Yönetim paneli (/admin) — docs/ROUTES.md §6. Grup zinciri (FR-ADM-001): web, auth, verified, active, staff, mfa,
 * admin.idle, admin.activity. Her rota ayrıca can: taşır (SEC-010, AdminRouteProtectionTest); kritik işlemler password.confirm.
 * Yeni rota önce docs/ROUTES.md içine eklenir.
 */

Route::get('/', DashboardController::class)->name('dashboard');

// §6.1 Sistem
Route::get('system/health', [SystemHealthController::class, 'show'])->middleware('can:settings.read')->name('system.health');
Route::post('system/failed-jobs/{uuid}/retry', [SystemHealthController::class, 'retryFailedJob'])->middleware('can:settings.update')->name('system.failed-jobs.retry');

// §6.2 Kullanıcılar
Route::get('users', [UserController::class, 'index'])->middleware('can:users.read')->name('users.index');
Route::get('users/{user:ulid}', [UserController::class, 'show'])->middleware('can:users.read')->name('users.show');
Route::get('users/{user:ulid}/security', [UserController::class, 'security'])->middleware('can:users.security_read')->name('users.security');
Route::post('users/{user:ulid}/status', [UserController::class, 'status'])->middleware(['can:users.suspend', 'password.confirm'])->name('users.status');
Route::post('users/{user:ulid}/resend-verification', [UserController::class, 'resendVerification'])->middleware(['can:users.update', 'throttle:3,60'])->name('users.resend-verification');

// §6.10 Personel, roller, branşlar
Route::get('staff', [StaffController::class, 'index'])->middleware('can:staff.read')->name('staff.index');
Route::get('staff/{user:ulid}', [StaffController::class, 'show'])->middleware('can:staff.read')->name('staff.show');
Route::post('staff/{user:ulid}/roles', [StaffController::class, 'assignRole'])->middleware(['can:staff.manage', 'password.confirm'])->name('staff.roles.assign');
Route::post('staff/{user:ulid}/roles/{role}/revoke', [StaffController::class, 'revokeRole'])->middleware(['can:staff.manage', 'password.confirm'])->name('staff.roles.revoke');

Route::get('roles', [RoleController::class, 'index'])->middleware('can:roles.read')->name('roles.index');
Route::get('roles/create', [RoleController::class, 'create'])->middleware('can:roles.manage')->name('roles.create');
Route::post('roles', [RoleController::class, 'store'])->middleware(['can:roles.manage', 'password.confirm'])->name('roles.store');
Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('can:roles.read')->name('roles.edit');
Route::post('roles/{role}', [RoleController::class, 'update'])->middleware(['can:roles.manage', 'password.confirm'])->name('roles.update');
Route::post('roles/{role}/delete', [RoleController::class, 'destroy'])->middleware(['can:roles.manage', 'password.confirm'])->name('roles.destroy');

Route::get('branches', [BranchController::class, 'index'])->middleware('can:staff.read')->name('branches.index');
Route::get('branches/create', [BranchController::class, 'create'])->middleware('can:branches.manage')->name('branches.create');
Route::post('branches', [BranchController::class, 'store'])->middleware('can:branches.manage')->name('branches.store');
Route::get('branches/{branch}', [BranchController::class, 'show'])->middleware('can:staff.read')->name('branches.show');
Route::post('branches/{branch}', [BranchController::class, 'update'])->middleware('can:branches.manage')->name('branches.update');
// Üye ekleme/çıkarma: branches.manage VEYA (branches.assign_members + branş lideri) — kontrol ManageBranchMembers'ta.
Route::post('branches/{branch}/members', [BranchController::class, 'storeMember'])->middleware('can:branches.assign_members')->name('branches.members.store');
Route::post('branches/{branch}/members/{user:ulid}/remove', [BranchController::class, 'destroyMember'])->middleware('can:branches.assign_members')->withoutScopedBindings()->name('branches.members.destroy');
Route::post('branches/{branch}/members/{user:ulid}/lead', [BranchController::class, 'toggleLead'])->middleware('can:branches.manage')->withoutScopedBindings()->name('branches.members.lead');

// §6.11 Denetim ve ayarlar
Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('can:audit.read')->name('audit-logs.index');
Route::get('audit-logs/{log}', [AuditLogController::class, 'show'])->middleware('can:audit.read')->name('audit-logs.show');
Route::get('activity-logs', ActivityLogController::class)->middleware('can:activity.read')->name('activity-logs.index');
// Marka (ad, logo, sekme ikonu) — docs/ROUTES.md §6, DESIGN §2.
Route::get('branding', [BrandingController::class, 'edit'])->middleware('can:settings.read')->name('branding');
Route::post('branding', [BrandingController::class, 'update'])->middleware(['can:settings.update', 'password.confirm'])->name('branding.update');
Route::post('branding/{asset}/remove', [BrandingController::class, 'remove'])->middleware(['can:settings.update', 'password.confirm'])->whereIn('asset', ['logo', 'icon'])->name('branding.remove');

Route::get('settings', [SettingsController::class, 'index'])->middleware('can:settings.read')->name('settings.index');
Route::get('settings/{group}', [SettingsController::class, 'edit'])->middleware('can:settings.read')->where('group', '[a-z_]+')->name('settings.edit');
Route::post('settings/{group}', [SettingsController::class, 'update'])->middleware(['can:settings.update', 'password.confirm'])->where('group', '[a-z_]+')->name('settings.update');

// §6.8–6.9 İçerik (CMS kaynak deseni) — tanımlar App\Services\Content\ContentResourceRegistry.
foreach (array_keys(ContentResourceRegistry::all()) as $resource) {
    $def = ContentResourceRegistry::get($resource);
    $can = fn (string $ability): string => 'can:'.ContentResourceRegistry::permission($resource, $ability);
    $prefix = $def['path'] ?? 'content/'.$resource;
    $name = substr(ContentResourceRegistry::routeName($resource), strlen('admin.')).'.';

    Route::prefix($prefix)->name($name)->group(function () use ($resource, $def, $can): void {
        $r = fn ($route) => $route->defaults('resource', $resource);

        $r(Route::get('/', [ContentController::class, 'index'])->middleware($can('read'))->name('index'));
        $r(Route::get('create', [ContentController::class, 'create'])->middleware($can('create'))->name('create'));
        $r(Route::post('/', [ContentController::class, 'store'])->middleware($can('create'))->name('store'));
        $r(Route::post('reorder', [ContentController::class, 'reorder'])->middleware($can('update'))->name('reorder'));
        $r(Route::get('{id}/edit', [ContentController::class, 'edit'])->middleware($can('read'))->whereNumber('id')->name('edit'));
        $r(Route::get('{id}/preview', [ContentController::class, 'preview'])->middleware([$can('read'), 'signed'])->whereNumber('id')->name('preview'));
        $r(Route::post('{id}', [ContentController::class, 'update'])->middleware($can('update'))->whereNumber('id')->name('update'));
        $r(Route::post('{id}/delete', [ContentController::class, 'destroy'])->middleware($can('delete'))->whereNumber('id')->name('destroy'));

        if ($def['publishable']) {
            foreach (['submit-review' => 'update', 'publish' => 'publish', 'unpublish' => 'publish', 'archive' => 'publish', 'restore' => 'publish'] as $transition => $ability) {
                $r(Route::post('{id}/'.$transition, [ContentController::class, 'transition'])->middleware($can($ability))
                    ->whereNumber('id')->defaults('transition', $transition)->name($transition));
            }
        }
    });
}

// §6.8 Kaynağa özel rotalar: bölüm düzenleyici, revizyonlar, kural sürümü.
Route::prefix('content/pages/{page}')->name('content.pages.')->whereNumber('page')->group(function (): void {
    Route::get('sections', [PageSectionController::class, 'index'])->middleware('can:content.read')->name('sections');
    Route::post('sections', [PageSectionController::class, 'store'])->middleware('can:content.update')->name('sections.store');
    Route::post('sections/{section}', [PageSectionController::class, 'update'])->middleware('can:content.update')->whereNumber('section')->name('sections.update');
    Route::post('sections/{section}/delete', [PageSectionController::class, 'destroy'])->middleware('can:content.update')->whereNumber('section')->name('sections.destroy');
    Route::post('sections/{section}/move', [PageSectionController::class, 'move'])->middleware('can:content.update')->whereNumber('section')->name('sections.reorder');
    Route::get('revisions', [PageSectionController::class, 'pageRevisions'])->middleware('can:content.read')->name('revisions');
    Route::post('revisions/{revision}/restore', [PageSectionController::class, 'restorePage'])->middleware('can:content.publish')->whereNumber('revision')->name('revisions.restore');
})->scopeBindings();
Route::get('content/rules/{rule}/revisions', [PageSectionController::class, 'ruleRevisions'])->middleware('can:content.read')->whereNumber('rule')->name('content.rules.revisions');
Route::get('content/rules-version', [PageSectionController::class, 'rulesVersion'])->middleware('can:content.publish')->name('content.rules.version');
Route::post('content/rules/bump-version', [PageSectionController::class, 'bumpRulesVersion'])->middleware('can:content.publish')->name('content.rules.bump-version');

// §6.8 Medya kütüphanesi
Route::get('media', [MediaController::class, 'index'])->middleware('can:content.read')->name('media.index');
Route::post('media', [MediaController::class, 'store'])->middleware(['can:media.upload', 'throttle:uploads'])->name('media.store');
Route::post('media/{media:ulid}', [MediaController::class, 'update'])->middleware('can:media.upload')->name('media.update');
Route::post('media/{media:ulid}/delete', [MediaController::class, 'destroy'])->middleware('can:media.delete')->name('media.destroy');

// §6.9 Mesaj şablonları ve iletişim mesajları
Route::get('templates', [TemplateController::class, 'index'])->middleware('can:templates.read')->name('templates.index');
Route::get('templates/{template}/edit', [TemplateController::class, 'edit'])->middleware('can:templates.read')->whereNumber('template')->name('templates.edit');
Route::post('templates/{template}', [TemplateController::class, 'update'])->middleware('can:templates.manage')->whereNumber('template')->name('templates.update');
Route::post('templates/{template}/preview', [TemplateController::class, 'preview'])->middleware('can:templates.read')->whereNumber('template')->name('templates.preview');
Route::post('templates/{template}/test-send', [TemplateController::class, 'testSend'])->middleware(['can:templates.manage', 'throttle:5,10'])->whereNumber('template')->name('templates.test-send');
Route::post('templates/{template}/revisions/{revision}/restore', [TemplateController::class, 'restore'])->middleware('can:templates.manage')->whereNumber(['template', 'revision'])->name('templates.revisions.restore');

Route::get('contact', [ContactMessageController::class, 'index'])->middleware('can:contact.read')->name('contact.index');
Route::get('contact/{message:ulid}', [ContactMessageController::class, 'show'])->middleware('can:contact.read')->name('contact.show');
Route::post('contact/{message:ulid}/status', [ContactMessageController::class, 'status'])->middleware('can:contact.read')->name('contact.status');

// §6.8 Akademi sınav soruları (Faz 10) — sınav ayarları CMS kaynak deseninde (`academy-quizzes`).
Route::prefix('academy-quizzes/{quiz}')->name('academy-quizzes.')->whereNumber('quiz')->group(function (): void {
    Route::get('questions', [QuizQuestionController::class, 'index'])->middleware('can:academy.manage')->name('questions');
    Route::post('questions', [QuizQuestionController::class, 'store'])->middleware('can:academy.manage')->name('questions.store');
    Route::post('questions/{question}/delete', [QuizQuestionController::class, 'destroy'])->middleware('can:academy.manage')->whereNumber('question')->name('questions.destroy');
});

// §6.4 Destek (Faz 6) — kapsam (branş) her uçta controller'da; kategoriler/makrolar yukarıdaki kaynak deseninde.
Route::prefix('support')->name('support.')->group(function (): void {
    Route::get('/', [SupportController::class, 'index'])->middleware('can:support.read')->name('index');
    Route::get('counters', [SupportController::class, 'counters'])->middleware(['can:support.read', 'throttle:poll'])->name('counters');
    Route::get('attachments/{attachment:ulid}', [SupportController::class, 'attachment'])->middleware('can:support.read')->name('attachments.show');
    Route::get('{ticket:ulid}', [SupportController::class, 'show'])->middleware('can:support.read')->where('ticket', '[0-9a-z]{26}')->name('show');
    Route::post('{ticket:ulid}/messages', [SupportController::class, 'message'])->middleware('can:support.reply')->where('ticket', '[0-9a-z]{26}')->name('messages.store');
    Route::post('{ticket:ulid}/status', [SupportController::class, 'status'])->middleware('can:support.reply')->where('ticket', '[0-9a-z]{26}')->name('status');
    Route::post('{ticket:ulid}/assign', [SupportController::class, 'assign'])->middleware('can:support.assign')->where('ticket', '[0-9a-z]{26}')->name('assign');
    Route::post('{ticket:ulid}/claim', [SupportController::class, 'claim'])->middleware('can:support.reply')->where('ticket', '[0-9a-z]{26}')->name('claim');
    Route::post('{ticket:ulid}/priority', [SupportController::class, 'priority'])->middleware('can:support.assign')->where('ticket', '[0-9a-z]{26}')->name('priority');
    Route::post('{ticket:ulid}/escalate', [SupportController::class, 'escalate'])->middleware('can:support.assign')->where('ticket', '[0-9a-z]{26}')->name('escalate');
    Route::post('{ticket:ulid}/reopen-decision', [SupportController::class, 'reopenDecision'])->middleware('can:support.reply')->where('ticket', '[0-9a-z]{26}')->name('reopen-decision');
});

// §6.6 Oyun hesapları ve karakterler (Faz 7)
Route::prefix('game-accounts')->name('game-accounts.')->group(function (): void {
    Route::get('/', [GameAccountController::class, 'index'])->middleware('can:game_accounts.read')->name('index');
    Route::post('manual-link', [GameAccountController::class, 'manualLink'])->middleware(['can:game_accounts.manage', 'password.confirm'])->name('manual-link');
    Route::post('links/{link}/approve-unlink', [GameAccountController::class, 'approveUnlink'])->middleware('can:game_accounts.manage')->whereNumber('link')->name('approve-unlink');
    Route::post('links/{link}/reject-unlink', [GameAccountController::class, 'rejectUnlink'])->middleware('can:game_accounts.manage')->whereNumber('link')->name('reject-unlink');
    Route::post('links/{link}/revoke', [GameAccountController::class, 'revoke'])->middleware(['can:game_accounts.manage', 'password.confirm'])->whereNumber('link')->name('revoke');
    Route::get('{account}', [GameAccountController::class, 'show'])->middleware('can:game_accounts.read')->where('account', '[0-9a-z]{26}')->name('show');
});
Route::get('characters', [CharacterController::class, 'index'])->middleware('can:characters.read')->name('characters.index');
Route::get('characters/{character}', [CharacterController::class, 'show'])->middleware('can:characters.read')->where('character', '[0-9a-z]{26}')->name('characters.show');
Route::post('characters/{character}/visibility', [CharacterController::class, 'visibility'])->middleware('can:characters.review')->where('character', '[0-9a-z]{26}')->name('characters.visibility');

// §6.6 Cihaz talepleri (Faz 8, R2)
Route::get('devices', [DeviceController::class, 'index'])->middleware('can:devices.read')->name('devices.index');
Route::post('devices/{device}/approve', [DeviceController::class, 'approve'])->middleware(['can:devices.approve', 'password.confirm'])->whereNumber('device')->name('devices.approve');
Route::post('devices/{device}/reject', [DeviceController::class, 'reject'])->middleware('can:devices.approve')->whereNumber('device')->name('devices.reject');

// §6.5 Başvurular (Faz 8) — branş kapsamı controller'da (Application::reviewableBy)
Route::prefix('applications')->name('applications.')->group(function (): void {
    Route::get('/', [ApplicationController::class, 'index'])->middleware('can:applications.read')->name('index');
    Route::get('{application}', [ApplicationController::class, 'show'])->middleware('can:applications.read')->where('application', '[0-9a-z]{26}')->name('show');
    Route::post('{application}/assign', [ApplicationController::class, 'assign'])->middleware('can:applications.review')->where('application', '[0-9a-z]{26}')->name('assign');
    Route::post('{application}/status', [ApplicationController::class, 'status'])->middleware('can:applications.review')->where('application', '[0-9a-z]{26}')->name('status');
    Route::post('{application}/messages', [ApplicationController::class, 'message'])->middleware('can:applications.review')->where('application', '[0-9a-z]{26}')->name('messages.store');
});

// §6.7 Cezalar, itirazlar ve oyun komutları (Faz 8)
Route::prefix('sanctions')->name('sanctions.')->group(function (): void {
    Route::get('/', [SanctionController::class, 'index'])->middleware('can:sanctions.read')->name('index');
    Route::get('create', [SanctionController::class, 'create'])->middleware('can:sanctions.create')->name('create');
    Route::post('/', [SanctionController::class, 'store'])->middleware(['can:sanctions.create', 'password.confirm'])->name('store');
    Route::get('{sanction}', [SanctionController::class, 'show'])->middleware('can:sanctions.read')->where('sanction', '[0-9a-z]{26}')->name('show');
    Route::post('{sanction}/revoke', [SanctionController::class, 'revoke'])->middleware('can:sanctions.revoke')->where('sanction', '[0-9a-z]{26}')->name('revoke');
});
Route::get('appeals', [SanctionController::class, 'appeals'])->middleware('can:sanctions.appeals.review')->name('appeals.index');
Route::get('appeals/{appeal}', [SanctionController::class, 'appeal'])->middleware('can:sanctions.appeals.review')->where('appeal', '[0-9a-z]{26}')->name('appeals.show');
Route::post('appeals/{appeal}/decide', [SanctionController::class, 'decide'])->middleware('can:sanctions.appeals.review')->where('appeal', '[0-9a-z]{26}')->name('appeals.decide');
Route::get('game-commands', [GameCommandController::class, 'index'])->middleware('can:game_commands.read')->name('game-commands.index');
// Tür bazlı izin controller'da (config/roleplay.php bridge.commands); burada en az okuma izni.
Route::post('game-commands', [GameCommandController::class, 'store'])->middleware(['can:game_commands.read', 'password.confirm'])->name('game-commands.store');
Route::post('game-commands/{command}/cancel', [GameCommandController::class, 'cancel'])->middleware('can:game_commands.read')->where('command', '[0-9a-z]{26}')->name('game-commands.cancel');

// §6.7 Ekonomi ve ödemeler (Faz 9). Dört göz Action'larda; kritik işlemler PC.
Route::prefix('economy')->name('economy.')->group(function (): void {
    Route::get('/', [EconomyController::class, 'index'])->middleware('can:wallet.read')->name('index');
    Route::get('transactions', [EconomyController::class, 'transactions'])->middleware('can:wallet.read')->name('transactions');
    Route::get('wallets/{wallet}', [EconomyController::class, 'wallet'])->middleware('can:wallet.read')->whereNumber('wallet')->name('wallets.show');
    Route::get('adjustments', [EconomyController::class, 'adjustments'])->middleware('can:wallet.read')->name('adjustments.index');
    Route::post('adjustments', [EconomyController::class, 'storeAdjustment'])->middleware(['can:wallet.adjust.request', 'password.confirm'])->name('adjustments.store');
    Route::post('adjustments/{adjustment}/approve', [EconomyController::class, 'approve'])->middleware(['can:wallet.adjust.approve', 'password.confirm'])->where('adjustment', '[0-9a-z]{26}')->name('adjustments.approve');
    Route::post('adjustments/{adjustment}/reject', [EconomyController::class, 'reject'])->middleware('can:wallet.adjust.approve')->where('adjustment', '[0-9a-z]{26}')->name('adjustments.reject');
    Route::post('transactions/{transaction}/reversal-request', [EconomyController::class, 'reversalRequest'])->middleware(['can:wallet.adjust.request', 'password.confirm'])->where('transaction', '[0-9a-z]{26}')->name('reversal-request');
});
Route::get('payments', [AdminPaymentController::class, 'index'])->middleware('can:payments.read')->name('payments.index');
Route::get('payments/reconciliation', [AdminPaymentController::class, 'reconciliation'])->middleware('can:payments.reconcile')->name('payments.reconciliation');
Route::post('payments/reconciliation/run', [AdminPaymentController::class, 'runReconciliation'])->middleware('can:payments.reconcile')->name('payments.reconciliation.run');
Route::get('payments/{order}', [AdminPaymentController::class, 'show'])->middleware('can:payments.read')->where('order', '[0-9a-z]{26}')->name('payments.show');
Route::post('payments/{order}/refund-request', [AdminPaymentController::class, 'refundRequest'])->middleware(['can:payments.refund.request', 'password.confirm'])->where('order', '[0-9a-z]{26}')->name('payments.refund-request');
Route::post('refunds/{refund}/approve', [AdminPaymentController::class, 'approveRefund'])->middleware(['can:payments.refund.approve', 'password.confirm'])->where('refund', '[0-9a-z]{26}')->name('refunds.approve');
Route::post('refunds/{refund}/reject', [AdminPaymentController::class, 'rejectRefund'])->middleware('can:payments.refund.approve')->where('refund', '[0-9a-z]{26}')->name('refunds.reject');

// §6.11 MTA entegrasyonu (Faz 7) — secret yalnızca oluşturma yanıtında bir kez
Route::prefix('integrations/mta')->name('integrations.mta')->middleware('can:integrations.manage')->group(function (): void {
    Route::get('/', [IntegrationController::class, 'index']);
    Route::post('servers', [IntegrationController::class, 'storeServer'])->middleware('password.confirm')->name('.servers.store');
    Route::post('servers/{server}', [IntegrationController::class, 'updateServer'])->middleware('password.confirm')->whereNumber('server')->name('.servers.update');
    Route::post('servers/{server}/credentials', [IntegrationController::class, 'storeCredential'])->middleware('password.confirm')->whereNumber('server')->name('.credentials.store');
    Route::post('credentials/{credential}/revoke', [IntegrationController::class, 'revokeCredential'])->middleware('password.confirm')->whereNumber('credential')->name('.credentials.revoke');
});

// §6.2 KVKK silme / anonimleştirme (Faz 6)
Route::post('users/{user:ulid}/anonymize', [UserController::class, 'anonymize'])->middleware(['can:users.anonymize', 'password.confirm'])->name('users.anonymize');
