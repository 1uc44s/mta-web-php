<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\GameAccounts\ManageLinks;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CharacterVisibilityRequest;
use App\Models\Character;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Karakterler (salt okunur kopya) — docs/ROUTES.md §6.6. Tek yazma işlemi profil görünürlüğü (audit). */
final class CharacterController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        return view('admin.characters.index', [
            'q' => $q,
            'characters' => Character::query()->with(['account:id,ulid,account_name', 'faction:id,name'])
                ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($q, '%_\\').'%'))
                ->orderByDesc('synced_at')->paginate(25)->withQueryString(),
        ]);
    }

    public function show(string $character): View
    {
        return view('admin.characters.show', [
            'character' => Character::query()->where('ulid', $character)
                ->with(['account.server:id,name', 'account.activeLink.user:id,ulid,username', 'faction:id,name', 'job:id,name'])->firstOrFail(),
        ]);
    }

    public function visibility(CharacterVisibilityRequest $request, ManageLinks $links, string $character): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $model = Character::query()->where('ulid', $character)->firstOrFail();

        $links->setCharacterVisibility($actor, $model, (bool) $request->validated('hidden'), (string) $request->validated('reason'));

        return back()->with('status', __('game.admin.done'));
    }
}
