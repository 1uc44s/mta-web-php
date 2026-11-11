<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ManageBranchMembers;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchMemberRequest;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use App\Models\Faction;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Branşlar — docs/ROUTES.md §6.10, ADMIN §5. Üye işlemleri ManageBranchMembers kurallarıyla. */
final class BranchController extends Controller
{
    public function __construct(private readonly AuditLogger $audit, private readonly ManageBranchMembers $members) {}

    public function index(): View
    {
        return view('admin.branches.index', [
            'branches' => Branch::query()->withCount('members')->with(['members' => fn ($q) => $q->wherePivot('is_lead', true)->select('users.id', 'username')])->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.branches.form', $this->formData(new Branch(['is_active' => true, 'type' => 'support'])));
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $branch = Branch::query()->create($this->attributes($request->validated()));
        $this->audit->record(AuditAction::BranchesCreated, $branch, ['after' => $branch->only(['slug', 'name', 'type'])]);

        return to_route('admin.branches.show', $branch)->with('toast', __('admin.branches.created'));
    }

    public function show(Request $request, Branch $branch): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('admin.branches.show', [
            ...$this->formData($branch),
            'memberList' => $branch->members()->orderByPivot('is_lead', 'desc')->orderBy('username')->get(['users.id', 'users.ulid', 'users.username']),
            'canManageMembers' => $this->members->canManage($actor, $branch),
        ]);
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $before = $branch->only(['slug', 'name', 'type', 'parent_id', 'is_active']);
        $branch->update($this->attributes($request->validated()));
        $this->audit->record(AuditAction::BranchesUpdated, $branch, ['before' => $before, 'after' => $branch->only(array_keys($before))]);

        return back()->with('toast', __('admin.branches.updated'));
    }

    public function storeMember(BranchMemberRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();
        $identifier = mb_strtolower(trim((string) $data['user']));
        $target = User::query()->where('username', $identifier)->orWhere('email', $identifier)->first();

        if ($target === null) {
            throw ValidationException::withMessages(['user' => __('admin.staff.errors.user_not_found')]);
        }

        /** @var User $actor */
        $actor = $request->user();
        $this->members->add($actor, $branch, $target, (bool) ($data['is_lead'] ?? false),
            isset($data['expires_at']) ? CarbonImmutable::parse((string) $data['expires_at']) : null);

        return back()->with('toast', __('admin.branches.member_added'));
    }

    public function destroyMember(Request $request, Branch $branch, User $user): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->members->remove($actor, $branch, $user);

        return back()->with('toast', __('admin.branches.member_removed'));
    }

    public function toggleLead(Request $request, Branch $branch, User $user): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->members->toggleLead($actor, $branch, $user);

        return back()->with('toast', __('admin.branches.lead_changed'));
    }

    /** @return array<string, mixed> */
    private function formData(Branch $branch): array
    {
        return [
            'branch' => $branch,
            'parents' => Branch::query()->whereKeyNot($branch->id ?? 0)->orderBy('name')->get(['id', 'name']),
            'factions' => Faction::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'name' => $data['name'], 'slug' => $data['slug'], 'type' => $data['type'],
            'parent_id' => $data['parent_id'] ?? null, 'faction_id' => $data['faction_id'] ?? null,
            'description' => $data['description'] ?? null, 'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
