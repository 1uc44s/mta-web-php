<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Game\BridgeController;
use App\Http\Controllers\Api\V1\Public\ServerStatusController;
use Illuminate\Support\Facades\Route;

/*
 * JSON API (/api/v1) — docs/ROUTES.md §7. public: api.public grubu, game: bridge grubu (Faz 7).
 * Yeni rota önce docs/ROUTES.md içine eklenir.
 */

Route::prefix('public')->name('api.public.')->middleware('throttle:public-api')->group(function (): void {
    Route::get('/server-status', ServerStatusController::class)->name('server-status');
});

// MTA köprüsü — docs/ROUTES.md §7.2. Sıra: throttle (anahtar başına) → bridge:{scope} (imza, pencere, nonce, kapsam).
Route::prefix('game')->name('api.game.')->controller(BridgeController::class)->group(function (): void {
    Route::middleware(['throttle:bridge', 'bridge'])->get('/ping', 'ping')->name('ping');
    Route::middleware(['throttle:bridge-heartbeat', 'bridge:heartbeat'])->post('/heartbeat', 'heartbeat')->name('heartbeat');

    Route::middleware(['throttle:bridge', 'bridge:events'])->group(function (): void {
        Route::post('/player/connected', 'playerConnected')->name('player.connected');
        Route::post('/player/disconnected', 'playerDisconnected')->name('player.disconnected');
        Route::post('/accounts/sync', 'accountsSync')->name('accounts.sync');
        Route::post('/characters/sync', 'charactersSync')->name('characters.sync');
    });

    Route::middleware(['throttle:bridge', 'bridge:account_link'])->post('/account-link/verify', 'verifyLink')->name('account-link.verify');
    Route::middleware(['throttle:bridge', 'bridge:account_link'])->post('/device-link/verify', 'verifyDevice')->name('device-link.verify');

    // Faz 8 — komut kuyruğu (pull). ARCHITECTURE §7.5
    Route::middleware(['throttle:bridge', 'bridge:commands'])->group(function (): void {
        Route::get('/commands/pending', 'commandsPending')->name('commands.pending');
        Route::post('/commands/result', 'commandsResult')->name('commands.result');
    });
});
