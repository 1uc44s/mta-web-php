<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Economy\ManageAdjustments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustmentRequest;
use App\Http\Requests\Admin\ReasonRequest;
use App\Models\PaymentOrder;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAdjustmentRequest;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ekonomi — docs/ROUTES.md §6.7 (ekonomi), SRS FR-WAL-006. Ledger salt okunur; tüm değişiklikler dört göz akışından
 * (`ManageAdjustments`) geçer.
 */
final class EconomyController extends Controller
{
    public function __construct(private readonly ManageAdjustments $adjustments) {}

    public function index(): View
    {
        $today = CarbonImmutable::today();
        $month = $today->startOfMonth();
        $sum = fn (string $direction, CarbonImmutable $from): int => (int) WalletTransaction::query()
            ->where('direction', $direction)->where('created_at', '>=', $from)->sum('amount');
        $revenue = fn (CarbonImmutable $from): int => (int) PaymentOrder::query()
            ->whereIn('status', ['succeeded', 'partially_refunded', 'refunded'])->where('paid_at', '>=', $from)->sum('amount_minor');

        return view('admin.economy.index', [
            'stats' => [
                'today' => ['in' => $sum('credit', $today), 'out' => $sum('debit', $today), 'revenue' => $revenue($today)],
                'month' => ['in' => $sum('credit', $month), 'out' => $sum('debit', $month), 'revenue' => $revenue($month)],
            ],
            'totalBalance' => (int) Wallet::query()->sum('balance'),
            'walletCount' => Wallet::query()->count(),
            'pendingAdjustments' => WalletAdjustmentRequest::query()->where('status', 'pending')->count(),
        ]);
    }

    public function transactions(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        return view('admin.economy.transactions', [
            'q' => $q,
            'transactions' => WalletTransaction::query()->with('wallet.user:id,username')
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->whereHas('wallet.user', fn ($u) => $u->where('username', $q))->orWhere('ulid', strtolower($q))))
                ->orderByDesc('id')->cursorPaginate(30)->withQueryString(),
        ]);
    }

    public function wallet(string $wallet): View
    {
        $model = Wallet::query()->with(['user:id,username,ulid', 'type'])->findOrFail((int) $wallet);

        return view('admin.economy.wallet', [
            'wallet' => $model,
            'transactions' => $model->transactions()->with('reversal:id,reversal_of_id,ulid')->latest('id')->paginate(30),
        ]);
    }

    public function adjustments(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');

        return view('admin.economy.adjustments', [
            'status' => $status,
            'requests' => WalletAdjustmentRequest::query()->with(['wallet.user:id,username', 'requester:id,username', 'reviewer:id,username', 'reversalOf:id,ulid'])
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function storeAdjustment(AdjustmentRequest $request, WalletService $wallets): RedirectResponse
    {
        $data = $request->validated();
        $user = User::query()->where('username', $data['username'])->firstOrFail();

        $this->adjustments->request($this->actor($request), $wallets->for($user), (string) $data['direction'], (int) $data['amount'], (string) $data['reason']);

        return redirect()->route('admin.economy.adjustments.index')->with('status', __('economy.admin.done'));
    }

    public function reversalRequest(AdjustmentRequest $request, string $transaction): RedirectResponse
    {
        $original = WalletTransaction::query()->where('ulid', $transaction)->with('wallet')->firstOrFail();
        $this->adjustments->request($this->actor($request), $original->wallet, $original->direction, $original->amount, (string) $request->validated('reason'), $original);

        return redirect()->route('admin.economy.adjustments.index')->with('status', __('economy.admin.done'));
    }

    public function approve(Request $request, string $adjustment): RedirectResponse
    {
        $note = $request->string('review_note')->trim()->limit(1000, '')->toString();
        $this->adjustments->approve($this->actor($request), $this->adjustment($adjustment), $note === '' ? null : $note);

        return back()->with('status', __('economy.admin.done'));
    }

    public function reject(ReasonRequest $request, string $adjustment): RedirectResponse
    {
        $this->adjustments->reject($this->actor($request), $this->adjustment($adjustment), (string) $request->validated('reason'));

        return back()->with('status', __('economy.admin.done'));
    }

    private function adjustment(string $ulid): WalletAdjustmentRequest
    {
        return WalletAdjustmentRequest::query()->where('ulid', $ulid)->firstOrFail();
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
