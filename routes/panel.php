<?php

declare(strict_types=1);

use App\Http\Controllers\Panel\DashboardController;
use Illuminate\Support\Facades\Route;

/*
 * Oyuncu paneli (/panel) — docs/ROUTES.md §5. Grup: panel (web + auth + active).
 * Yeni rota önce docs/ROUTES.md içine eklenir.
 */

Route::get('/', DashboardController::class)->name('dashboard');
