<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Payments\PaymentInbox;
use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Services\Payments\FakeProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Sahte ödeme ekranı — docs/ROUTES.md §5.7 (`payments.fake.*`). Yalnızca `PAYMENTS_PROVIDER=fake` ve production dışı;
 * imzalı URL ile açılır. Sonuç, gerçek sağlayıcıyla **aynı** gelen kutusu yolundan (imza → payment_events → job) işlenir.
 */
final class FakeCheckoutController extends Controller
{
    public function show(Request $request, string $order): View
    {
        $model = $this->order($request, $order);

        return view('payments.fake', [
            'order' => $model,
            'completeUrl' => URL::temporarySignedRoute('payments.fake.complete', now()->addMinutes(30), ['order' => $model->ulid]),
        ]);
    }

    public function complete(Request $request, PaymentInbox $inbox, string $order): RedirectResponse
    {
        $model = $this->order($request, $order);
        $outcome = $request->input('outcome') === 'succeeded' ? 'succeeded' : 'failed';
        $body = FakeProvider::eventBody($model, $outcome);

        $inbox->receive('fake', $body, ['x-fake-signature' => [FakeProvider::sign($body)]]);

        return redirect()->route('panel.payments.show', $model);
    }

    private function order(Request $request, string $ulid): PaymentOrder
    {
        abort_unless(config('roleplay.payments.provider') === 'fake' && ! app()->isProduction(), 404);

        return PaymentOrder::query()->where('ulid', $ulid)->where('provider', 'fake')
            ->where('user_id', $request->user()?->getAuthIdentifier())->firstOrFail();
    }
}
