<?php

declare(strict_types=1);

use App\Http\Controllers\Panel\AcademyController;
use App\Http\Controllers\Panel\ApplicationController;
use App\Http\Controllers\Panel\CharacterController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\DeviceController;
use App\Http\Controllers\Panel\GameAccountController;
use App\Http\Controllers\Panel\NotificationController;
use App\Http\Controllers\Panel\PaymentController;
use App\Http\Controllers\Panel\SanctionController;
use App\Http\Controllers\Panel\SecurityController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\SupportController;
use App\Http\Controllers\Panel\TwoFactorController;
use App\Http\Controllers\Panel\WalletController;
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

// §5.3 Oyun hesapları ve karakterler (Faz 7) — kod üretimi doğrulanmış e-posta + throttle:link-code
Route::get('game-accounts', [GameAccountController::class, 'index'])->name('game-accounts.index');
Route::post('game-accounts/link-code', [GameAccountController::class, 'linkCode'])->middleware(['verified', 'throttle:link-code'])->name('game-accounts.link-code');
Route::post('game-accounts/link-code/cancel', [GameAccountController::class, 'cancel'])->name('game-accounts.link-code.cancel');
Route::get('game-accounts/link-status', [GameAccountController::class, 'status'])->middleware('throttle:poll')->name('game-accounts.link-status');
Route::post('game-accounts/{link}/unlink-request', [GameAccountController::class, 'unlinkRequest'])->middleware('password.confirm')->whereNumber('link')->name('game-accounts.unlink-request');
Route::get('characters', [CharacterController::class, 'index'])->name('characters.index');
Route::get('characters/{character}', [CharacterController::class, 'show'])->where('character', '[0-9A-Za-z]{26}')->name('characters.show');

// §5.3 Cihazlar (Faz 8, F:devices)
Route::middleware('feature:devices')->prefix('devices')->name('devices.')->group(function (): void {
    Route::get('/', [DeviceController::class, 'index'])->name('index');
    Route::post('link-code', [DeviceController::class, 'linkCode'])->middleware(['verified', 'throttle:link-code'])->name('link-code');
    Route::post('{device}/revoke', [DeviceController::class, 'revoke'])->middleware('password.confirm')->whereNumber('device')->name('revoke');
});

// §5.5 Başvurular (Faz 8, F:applications) — doğrulanmış e-posta
Route::middleware(['feature:applications', 'verified'])->prefix('applications')->name('applications.')->group(function (): void {
    Route::get('/', [ApplicationController::class, 'index'])->name('index');
    Route::get('new/{type}', [ApplicationController::class, 'create'])->where('type', '[a-z0-9-]+')->name('create');
    Route::post('new/{type}', [ApplicationController::class, 'store'])->where('type', '[a-z0-9-]+')->middleware('throttle:10,60')->name('store');
    Route::get('{application}', [ApplicationController::class, 'show'])->where('application', '[0-9a-z]{26}')->name('show');
    Route::post('{application}', [ApplicationController::class, 'update'])->where('application', '[0-9a-z]{26}')->name('update');
    Route::post('{application}/messages', [ApplicationController::class, 'message'])->where('application', '[0-9a-z]{26}')->middleware('throttle:20,10')->name('messages.store');
    Route::post('{application}/cancel', [ApplicationController::class, 'cancel'])->where('application', '[0-9a-z]{26}')->name('cancel');
});

// §5.6 Cezalar (Faz 8, F:sanctions)
Route::middleware('feature:sanctions')->prefix('sanctions')->name('sanctions.')->group(function (): void {
    Route::get('/', [SanctionController::class, 'index'])->name('index');
    Route::get('{sanction}', [SanctionController::class, 'show'])->where('sanction', '[0-9a-z]{26}')->name('show');
    Route::post('{sanction}/appeal', [SanctionController::class, 'appeal'])->where('sanction', '[0-9a-z]{26}')->middleware('throttle:5,60')->name('appeal');
});

// §5.7 Cüzdan ve ödeme (Faz 9, F:wallet / F:payments; FR-PAY-006 kapalıyken 404)
Route::middleware('feature:wallet')->group(function (): void {
    Route::get('wallet', [WalletController::class, 'index'])->name('wallet');
    Route::get('transactions', [WalletController::class, 'transactions'])->name('transactions');
});
Route::middleware(['feature:payments', 'verified'])->group(function (): void {
    Route::get('top-up', [PaymentController::class, 'topUp'])->name('top-up');
    Route::post('payments/checkout', [PaymentController::class, 'checkout'])->middleware('throttle:10,60')->name('payments.checkout');
    Route::get('payments/{order}', [PaymentController::class, 'show'])->where('order', '[0-9a-z]{26}')->name('payments.show');
    Route::get('payments/{order}/status', [PaymentController::class, 'status'])->where('order', '[0-9a-z]{26}')->middleware('throttle:poll')->name('payments.status');
});

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
