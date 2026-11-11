<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Karakterlerim — docs/ROUTES.md §5.3, SRS FR-ACC-008/009. Yalnızca aktif bağlantılı hesapların karakterleri;
 * başkasının karakter ULID'si 404 (IDOR). Detayda yalnızca `character_public_fields` allowlist'i.
 */
final class CharacterController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('panel.characters.index', [
            'characters' => Character::query()->ownedBy($user)->with(['account:id,account_name', 'faction:id,name', 'job:id,name'])
                ->orderByDesc('last_seen_at')->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, string $character): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('panel.characters.show', [
            'character' => Character::query()->ownedBy($user)->where('ulid', $character)
                ->with(['account:id,account_name', 'faction:id,name', 'job:id,name'])->firstOrFail(),
        ]);
    }
}
