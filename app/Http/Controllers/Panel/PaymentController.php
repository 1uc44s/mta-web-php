<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Actions\Payments\StartCheckout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\CheckoutRequest;
use App\Models\PaymentOrder;
use App\Models\PaymentPackage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Kredi yükleme — docs/ROUTES.md §5.7 (F:payments), SRS FR-PAY-002/004. Dönüş sayfası bakiye **yüklemez**; yalnızca
 * sipariş durumunu gösterir. Başkasının siparişi 404.
 */
final class PaymentController extends Controller
{
    public function topUp(): View
    {
        return view('panel.wallet.top-up', [
            'packages' => PaymentPackage::query()->with('walletType:id,unit_label,is_purchasable')->where('is_active', true)
                ->whereHas('walletType', fn ($q) => $q->where('is_purchasable', true)->where('is_active', true))
                ->orderBy('sort')->get(),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function checkout(CheckoutRequest $request, StartCheckout $checkout): RedirectResponse
    {
        $data = $request->validated();
        $result = $checkout->handle($this->user($request), (int) $data['package_id'], (string) $data['idempotency_key']);

        // FR-PAY-002: sağlayıcıya 303. Tekrar gönderilen form mevcut siparişin dönüş sayfasına gider.
        return $result['redirect'] !== null
            ? redirect()->away($result['redirect'], 303)
            : redirect()->route('panel.payments.show', $result['order'], 303);
    }

    public function show(Request $request, string $order): View
    {
        return view('panel.wallet.payment', ['order' => $this->owned($request, $order)->load('wallet.type:id,unit_label')]);
    }

    public function status(Request $request, string $order): JsonResponse
    {
        $model = $this->owned($request, $order);

        return response()->json(['success' => true, 'data' => ['status' => $model->status, 'pending' => $model->isPending()]])
            ->header('Cache-Control', 'no-store, private');
    }

    private function owned(Request $request, string $ulid): PaymentOrder
    {
        return PaymentOrder::query()->where('user_id', $this->user($request)->id)->where('ulid', $ulid)->firstOrFail();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
