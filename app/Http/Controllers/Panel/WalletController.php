<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletType;
use App\Services\Settings\SettingsRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Cüzdanım ve işlemlerim — docs/ROUTES.md §5.7 (F:wallet). Yalnızca kendi cüzdanları; ledger salt okunur gösterilir. */
final class WalletController extends Controller
{
    public function index(Request $request, SettingsRepository $settings): View
    {
        $user = $this->user($request);

        return view('panel.wallet.index', [
            'paymentsEnabled' => $settings->bool('features.payments'),
            'types' => WalletType::query()->where('is_active', true)->orderBy('sort')->get(),
            'wallets' => Wallet::query()->where('user_id', $user->id)->get()->keyBy('wallet_type_id'),
            'recent' => WalletTransaction::query()->whereIn('wallet_id', Wallet::query()->where('user_id', $user->id)->select('id'))
                ->latest('id')->limit(5)->get(),
        ]);
    }

    public function transactions(Request $request): View
    {
        $user = $this->user($request);
        $direction = (string) $request->query('direction', 'all');

        return view('panel.wallet.transactions', [
            'direction' => $direction,
            'transactions' => WalletTransaction::query()->whereIn('wallet_id', Wallet::query()->where('user_id', $user->id)->select('id'))
                ->when(in_array($direction, ['credit', 'debit'], true), fn ($q) => $q->where('direction', $direction))
                ->with('wallet.type:id,unit_label')->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
