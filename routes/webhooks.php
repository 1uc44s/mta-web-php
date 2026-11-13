<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
 * Webhook'lar (/webhooks) — docs/ROUTES.md §7.3. Yalnızca imza doğrulamalı uçlar; CSRF istisnası buraya özeldir.
 * Yeni rota önce docs/ROUTES.md içine eklenir.
 */

// Faz 9 — ödeme sağlayıcısı (FR-PAY-003). İmza doğrulaması sağlayıcı adapter'ında; ödeme modülü kapalıyken 404 (FR-PAY-006).
Route::post('/payments/{provider}', PaymentWebhookController::class)
    ->middleware(['throttle:webhooks', 'feature:payments'])
    ->where('provider', '[a-z]+')
    ->name('payments');
