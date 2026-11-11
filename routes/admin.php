<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\UserController;
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
