<?php

declare(strict_types=1);

use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
 * Oyuncu paneli (/panel) — docs/ROUTES.md §5. Grup: panel (web + auth + active).
 * Yeni rota önce docs/ROUTES.md içine eklenir.
 */

Route::get('/', DashboardController::class)->name('dashboard');

// §5 Güvenlik — Faz 4: 2FA kurulum sihirbazı (personel için zorunlu, FR-AUTH-017). Diğer güvenlik ekranları Faz 6.
Route::get('security/two-factor', TwoFactorController::class)->name('security.two-factor');
