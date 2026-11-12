<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Actions\Moderation\ManageSanctions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\MessageBodyRequest;
use App\Models\Sanction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ceza geçmişi ve itiraz — docs/ROUTES.md §5.6, SRS FR-MOD-004/005. Yalnızca oyuncuya gösterilebilir kolonlar seçilir:
 * `internal_note`, `evidence_url` ve `issued_by` hiçbir sorguda yüklenmez (veren "Yönetim Ekibi" olarak görünür).
 */
final class SanctionController extends Controller
{
    /** @var list<string> */
    private const PUBLIC_COLUMNS = ['id', 'ulid', 'type', 'public_reason', 'starts_at', 'ends_at', 'status', 'appealable', 'created_at'];

    public function index(Request $request): View
    {
        return view('panel.sanctions.index', [
            'sanctions' => Sanction::query()->visibleTo($this->user($request))->latest('id')->get(self::PUBLIC_COLUMNS),
        ]);
    }

    public function show(Request $request, string $sanction): View
    {
        $model = Sanction::query()->visibleTo($this->user($request))->where('ulid', $sanction)->firstOrFail(self::PUBLIC_COLUMNS);

        return view('panel.sanctions.show', [
            'sanction' => $model,
            'appeal' => $model->appeal()->first(['id', 'ulid', 'sanction_id', 'body', 'status', 'decision_note_public', 'decided_at', 'created_at']),
        ]);
    }

    public function appeal(MessageBodyRequest $request, ManageSanctions $sanctions, string $sanction): RedirectResponse
    {
        $user = $this->user($request);
        $model = Sanction::query()->visibleTo($user)->where('ulid', $sanction)->firstOrFail();

        $sanctions->appeal($user, $model, (string) $request->validated('body'));

        return redirect()->route('panel.sanctions.show', $model)->with('status', __('moderation.appeal_sent'));
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
