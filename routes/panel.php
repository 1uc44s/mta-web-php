<?php

declare(strict_types=1);

use App\Http\Controllers\Panel\AcademyController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\NotificationController;
use App\Http\Controllers\Panel\SecurityController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\SupportController;
use App\Http\Controllers\Panel\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
 * Oyuncu paneli (/panel) — docs/ROUTES.md §5. Grup: panel (web + auth + active).
 * Yeni rota önce docs/ROUTES.md içine eklenir.
 */

Route::get('/', DashboardController::class)->name('dashboard');

// §5 Güvenlik — Faz 4: 2FA kurulum sihirbazı (personel için zorunlu, FR-AUTH-017). Diğer güvenlik ekranları Faz 6.
Route::get('security/two-factor', TwoFactorController::class)->name('security.two-factor');

// §5.3 Akademi, kurallar ve duyurular (Faz 5)
Route::get('academy', [AcademyController::class, 'index'])->name('academy');
Route::post('academy/topics/{topic:id}/complete', [AcademyController::class, 'complete'])->whereNumber('topic')->name('academy.complete');
Route::post('rules/acknowledge', [AcademyController::class, 'acknowledgeRules'])->name('rules.acknowledge');
Route::post('announcements/{announcement:ulid}/read', [AcademyController::class, 'readAnnouncement'])->name('announcements.read');

// §5.1 Bildirimler ve ayarlar (Faz 6)
Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::get('notifications/count', [NotificationController::class, 'count'])->middleware('throttle:poll')->name('notifications.count');
Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->whereUuid('id')->name('notifications.read');
Route::get('settings', [SettingsController::class, 'show'])->name('settings');
Route::post('settings/preferences', [SettingsController::class, 'preferences'])->name('settings.preferences');
Route::post('settings/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');

// §5.8 Güvenlik ve hesap (Faz 6)
Route::get('security', [SecurityController::class, 'show'])->name('security');
Route::post('security/password', [SecurityController::class, 'password'])->middleware('throttle:5,10')->name('security.password');
Route::post('security/email', [SecurityController::class, 'email'])->middleware(['password.confirm', 'throttle:3,60'])->name('security.email');
Route::get('security/email/confirm/{token}', [SecurityController::class, 'emailConfirm'])->where('token', '[A-Za-z0-9]{64}')->name('security.email.confirm');
Route::post('security/email/confirm/{token}', [SecurityController::class, 'emailConfirmStore'])->where('token', '[A-Za-z0-9]{64}')->name('security.email.confirm.store');
Route::post('security/sessions/revoke-others', [SecurityController::class, 'revokeOthers'])->middleware('password.confirm')->name('security.sessions.revoke-others');
Route::post('security/sessions/revoke-all', [SecurityController::class, 'revokeAll'])->middleware('password.confirm')->name('security.sessions.revoke-all');
Route::post('security/sessions/{session}/revoke', [SecurityController::class, 'revokeSession'])->middleware('password.confirm')->where('session', '[A-Za-z0-9]{20,100}')->name('security.sessions.revoke');
Route::get('security/events', [SecurityController::class, 'events'])->name('security.events');
Route::post('account/delete-request', [SecurityController::class, 'deleteRequest'])->middleware('password.confirm')->name('account.delete-request');
Route::post('account/delete-cancel', [SecurityController::class, 'deleteCancel'])->name('account.delete-cancel');

// §5.4 Destek — doğrulanmış e-posta gerekir (panel.verified)
Route::middleware('verified')->prefix('support')->name('support.')->group(function (): void {
    Route::get('/', [SupportController::class, 'index'])->name('index');
    Route::get('create', [SupportController::class, 'create'])->name('create');
    Route::post('/', [SupportController::class, 'store'])->middleware(['throttle:support-create', 'throttle:uploads'])->name('store');
    Route::get('attachments/{attachment:ulid}', [SupportController::class, 'attachment'])->name('attachments.show');
    Route::get('{ticket:ulid}', [SupportController::class, 'show'])->name('show');
    Route::post('{ticket:ulid}/messages', [SupportController::class, 'message'])->middleware(['throttle:support-message', 'throttle:uploads'])->name('messages.store');
    Route::post('{ticket:ulid}/close', [SupportController::class, 'close'])->name('close');
    Route::post('{ticket:ulid}/reopen-request', [SupportController::class, 'reopenRequest'])->name('reopen-request');
});
