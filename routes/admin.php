<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
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
