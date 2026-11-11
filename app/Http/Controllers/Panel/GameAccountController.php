<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Actions\GameAccounts\LinkCodes;
use App\Actions\GameAccounts\ManageLinks;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\UnlinkRequest;
use App\Models\GameAccountLink;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Oyun hesabı bağlama — docs/ROUTES.md §5.3, SRS FR-ACC-001..007, ARCHITECTURE §7.4.
 * Kod düz metin olarak yalnızca üretimden hemen sonraki sayfada (tek seferlik oturum anahtarı, `pull`) gösterilir;
 * polling uç noktası kodu asla döndürmez.
 */
final class GameAccountController extends Controller
{
    private const FRESH_CODE_KEY = 'game_link_code.fresh';

    public function __construct(private readonly LinkCodes $codes, private readonly ManageLinks $links) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $fresh = $request->session()->pull(self::FRESH_CODE_KEY);

        return view('panel.game-accounts', [
            'links' => GameAccountLink::query()->where('user_id', $user->id)->whereIn('status', GameAccountLink::ACTIVE)
                ->with('account.server:id,name')->latest('linked_at')->get(),
            'codeStatus' => $this->codes->status($user),
            'freshCode' => is_string($fresh) ? $fresh : null,
            'maxLinks' => $this->codes->maxLinks(),
            'activeCount' => $this->codes->activeLinkCount($user),
        ]);
    }

    public function linkCode(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $issued = $this->codes->issue($user);

        $request->session()->put(self::FRESH_CODE_KEY, $issued['code']);

        return redirect()->route('panel.game-accounts.index');
    }

    public function cancel(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->codes->cancelPending($user);

        return redirect()->route('panel.game-accounts.index')->with('status', __('game.code.cancelled'));
    }

    public function status(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['success' => true, 'data' => $this->codes->status($user)])
            ->header('Cache-Control', 'no-store, private');
    }

    public function unlinkRequest(UnlinkRequest $request, string $link): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $model = GameAccountLink::query()->where('user_id', $user->id)->findOrFail((int) $link);

        $this->links->requestUnlink($user, $model, (string) $request->validated('reason'));

        return redirect()->route('panel.game-accounts.index')->with('status', __('game.link.unlink_sent'));
    }
}
