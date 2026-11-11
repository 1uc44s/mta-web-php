<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Public\ServerStatusController;
use Illuminate\Support\Facades\Route;

/*
 * JSON API (/api/v1) — docs/ROUTES.md §7. public: api.public grubu, game: bridge grubu (Faz 7).
 * Yeni rota önce docs/ROUTES.md içine eklenir.
 */

Route::prefix('public')->name('api.public.')->middleware('throttle:public-api')->group(function (): void {
    Route::get('/server-status', ServerStatusController::class)->name('server-status');
});
