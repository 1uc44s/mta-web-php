<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Moderation\ManageSanctions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecisionRequest;
use App\Http\Requests\Admin\ReasonRequest;
use App\Http\Requests\Admin\SanctionRequest;
use App\Models\GameCommand;
use App\Models\Rule;
use App\Models\Sanction;
use App\Models\SanctionAppeal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Cezalar ve itirazlar — docs/ROUTES.md §6.7, SRS FR-MOD-001..006. */
final class SanctionController extends Controller
{
    public function __construct(private readonly ManageSanctions $sanctions) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'active');
        $q = trim((string) $request->query('q', ''));

        return view('admin.sanctions.index', [
            'status' => $status,
            'q' => $q,
            'sanctions' => Sanction::query()->with(['user:id,username', 'account:id,ulid,account_name', 'issuer:id,username'])
                ->when(in_array($status, ['active', 'expired', 'revoked'], true), fn ($query) => $query->where('status', $status))
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->whereHas('user', fn ($u) => $u->where('username', $q))
                    ->orWhereHas('account', fn ($a) => $a->where('account_name', $q)->orWhere('mta_account_ref', $q))))
                ->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.sanctions.create', [
            'prefill' => ['user' => (string) $request->query('user', ''), 'account' => (string) $request->query('account', '')],
            'rules' => Rule::query()->orderBy('code')->get(['id', 'code', 'title']),
            'canPerm' => $this->actor($request)->hasPermission('sanctions.ban_permanent'),
        ]);
    }

    public function store(SanctionRequest $request): RedirectResponse
    {
        $sanction = $this->sanctions->issue($this->actor($request), $request->validated());

        return redirect()->route('admin.sanctions.show', $sanction)->with('status', __('moderation.admin.done'));
    }

    public function show(string $sanction): View
    {
        $model = Sanction::query()->where('ulid', $sanction)->with(['user:id,username,ulid', 'account:id,ulid,account_name,mta_account_ref', 'character:id,ulid,name', 'rule:id,code,title', 'issuer:id,username', 'appeal'])->firstOrFail();

        return view('admin.sanctions.show', [
            'sanction' => $model,
            'commands' => GameCommand::query()->where('source_type', 'sanction')->where('source_id', $model->id)->orderBy('id')->get(),
        ]);
    }

    public function revoke(ReasonRequest $request, string $sanction): RedirectResponse
    {
        $this->sanctions->revoke($this->actor($request), Sanction::query()->where('ulid', $sanction)->firstOrFail(), (string) $request->validated('reason'));

        return back()->with('status', __('moderation.admin.done'));
    }

    public function appeals(Request $request): View
    {
        $status = (string) $request->query('status', 'open');

        return view('admin.sanctions.appeals', [
            'status' => $status,
            'appeals' => SanctionAppeal::query()->with(['sanction:id,ulid,type,issued_by', 'user:id,username'])
                ->when($status === 'open', fn ($q) => $q->whereIn('status', ['submitted', 'in_review']))
                ->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function appeal(Request $request, string $appeal): View
    {
        $model = SanctionAppeal::query()->where('ulid', $appeal)->with(['sanction.issuer:id,username', 'sanction.user:id,username', 'user:id,username'])->firstOrFail();

        return view('admin.sanctions.appeal', [
            'appeal' => $model,
            'isIssuer' => $model->sanction->issued_by === $this->actor($request)->id,
        ]);
    }

    public function decide(DecisionRequest $request, string $appeal): RedirectResponse
    {
        $data = $request->validated();
        $this->sanctions->decideAppeal($this->actor($request), SanctionAppeal::query()->where('ulid', $appeal)->firstOrFail(),
            $data['decision'] === 'accepted', (string) $data['note_public'], $data['note_internal'] ?? null);

        return back()->with('status', __('moderation.admin.done'));
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
