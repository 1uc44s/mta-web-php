<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\PaymentInbox;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `POST /webhooks/payments/{provider}` — docs/ROUTES.md §7.3. Her zaman hızlı yanıt: işleme asenkron (job).
 * İmza hatalıysa 400; aynı olay tekrar gelirse 200 + `duplicate:true` (yan etki yok).
 */
final class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentInbox $inbox, string $provider): JsonResponse
    {
        $result = $inbox->receive($provider, (string) $request->getContent(), $request->headers->all());

        return $result['accepted']
            ? new JsonResponse(['received' => true, 'duplicate' => $result['duplicate']])
            : new JsonResponse(['received' => false], 400);
    }
}
