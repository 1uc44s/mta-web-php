<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\GameAccounts\ManageLinks;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManualLinkRequest;
use App\Http\Requests\Admin\ReasonRequest;
use App\Models\GameAccountLink;
use App\Models\MtaAccount;
use App\Models\MtaServer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Oyun hesapları — docs/ROUTES.md §6.6, SRS FR-ACC-005/006. Her geçiş audit'e yazılır (ManageLinks). */
final class GameAccountController extends Controller
{
    public function __construct(private readonly ManageLinks $links) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');

        $accounts = MtaAccount::query()->with(['server:id,name', 'activeLink.user:id,username,ulid'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('account_name', 'like', '%'.addcslashes($q, '%_\\').'%')
                ->orWhere('mta_account_ref', $q)
                ->orWhereHas('links.user', fn ($u) => $u->where('username', $q))))
            ->when($filter === 'unlink_requested', fn ($query) => $query->whereHas('links', fn ($l) => $l->where('status', 'unlink_requested')))
            ->when($filter === 'unlinked', fn ($query) => $query->whereDoesntHave('activeLink'))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.game-accounts.index', [
            'accounts' => $accounts, 'q' => $q, 'filter' => $filter,
            'servers' => MtaServer::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(string $account): View
    {
        $model = MtaAccount::query()->where('ulid', $account)->with(['server:id,name', 'characters'])->firstOrFail();

        return view('admin.game-accounts.show', [
            'account' => $model,
            'links' => $model->links()->with(['user:id,username,ulid'])->latest('id')->get(),
        ]);
    }

    public function approveUnlink(ReasonRequest $request, string $link): RedirectResponse
    {
        $this->links->approveUnlink($this->actor($request), $this->link($link), (string) $request->validated('reason'));

        return back()->with('status', __('game.admin.done'));
    }

    public function rejectUnlink(ReasonRequest $request, string $link): RedirectResponse
    {
        $this->links->rejectUnlink($this->actor($request), $this->link($link), (string) $request->validated('reason'));

        return back()->with('status', __('game.admin.done'));
    }

    public function revoke(ReasonRequest $request, string $link): RedirectResponse
    {
        $this->links->revoke($this->actor($request), $this->link($link), (string) $request->validated('reason'));

        return back()->with('status', __('game.admin.done'));
    }

    public function manualLink(ManualLinkRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $link = $this->links->manualLink(
            $this->actor($request),
            User::query()->where('username', $data['username'])->firstOrFail(),
            MtaServer::query()->findOrFail((int) $data['mta_server_id']),
            (string) $data['mta_account_ref'],
            (string) $data['account_name'],
            (string) $data['reason'],
            (string) $data['evidence'],
        );

        return redirect()->route('admin.game-accounts.show', $link->account)->with('status', __('game.admin.done'));
    }

    private function link(string $id): GameAccountLink
    {
        return GameAccountLink::query()->with('account')->findOrFail((int) $id);
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
