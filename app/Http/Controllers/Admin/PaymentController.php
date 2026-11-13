<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Economy\ReconcileWallets;
use App\Actions\Payments\ManageRefunds;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReasonRequest;
use App\Http\Requests\Admin\RefundRequest;
use App\Models\PaymentOrder;
use App\Models\Refund;
use App\Models\User;
use App\Models\WalletReconciliationRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Ödemeler, iadeler ve mutabakat — docs/ROUTES.md §6.7 (ödemeler), SRS FR-PAY-005, FR-WAL-007. */
final class PaymentController extends Controller
{
    public function __construct(private readonly ManageRefunds $refunds) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $q = trim((string) $request->query('q', ''));

        return view('admin.payments.index', [
            'status' => $status,
            'q' => $q,
            'orders' => PaymentOrder::query()->with('user:id,username')
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('order_number', strtoupper($q))->orWhereHas('user', fn ($u) => $u->where('username', $q))))
                ->latest('id')->paginate(25)->withQueryString(),
            'pendingRefunds' => Refund::query()->whereIn('status', ['requested', 'requires_review'])->with(['order:id,ulid,order_number', 'requester:id,username'])->latest('id')->get(),
        ]);
    }

    public function show(string $order): View
    {
        $model = PaymentOrder::query()->where('ulid', $order)->with(['user:id,username,ulid', 'package:id,name', 'events' => fn ($q) => $q->orderBy('id'),
            'refunds' => fn ($q) => $q->with(['requester:id,username', 'approver:id,username'])->orderBy('id')])->firstOrFail();

        return view('admin.payments.show', ['order' => $model]);
    }

    public function refundRequest(RefundRequest $request, string $order): RedirectResponse
    {
        $data = $request->validated();
        $this->refunds->request($this->actor($request), PaymentOrder::query()->where('ulid', $order)->firstOrFail(), (int) $data['amount_minor'], (string) $data['reason']);

        return back()->with('status', __('economy.admin.done'));
    }

    public function approveRefund(Request $request, string $refund): RedirectResponse
    {
        $this->refunds->approve($this->actor($request), Refund::query()->where('ulid', $refund)->firstOrFail());

        return back()->with('status', __('economy.admin.done'));
    }

    public function rejectRefund(ReasonRequest $request, string $refund): RedirectResponse
    {
        $this->refunds->reject($this->actor($request), Refund::query()->where('ulid', $refund)->firstOrFail(), (string) $request->validated('reason'));

        return back()->with('status', __('economy.admin.done'));
    }

    public function reconciliation(): View
    {
        return view('admin.payments.reconciliation', [
            'runs' => WalletReconciliationRun::query()->with('trigger:id,username')->latest('id')->limit(30)->get(),
        ]);
    }

    public function runReconciliation(Request $request, ReconcileWallets $reconcile): RedirectResponse
    {
        $reconcile->run($this->actor($request));

        return redirect()->route('admin.payments.reconciliation')->with('status', __('economy.admin.done'));
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
