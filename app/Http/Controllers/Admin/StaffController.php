<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\AssignRole;
use App\Actions\Admin\RevokeRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Requests\Admin\ReasonRequest;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Personel — docs/ROUTES.md §6.10, ADMIN §6. */
final class StaffController extends Controller
{
    public function index(): View
    {
        $staff = User::query()
            ->whereHas('roles', fn ($q) => $q->where('is_staff', true))
            ->with(['roles:id,slug,name,priority,color', 'branches:id,name'])
            ->orderBy('username')
            ->paginate(30);

        return view('admin.staff.index', ['staff' => $staff]);
    }

    public function show(Request $request, User $user): View
    {
        $user->load(['roles:id,slug,name,priority,color', 'branches:id,name,slug']);

        /** @var User $actor */
        $actor = $request->user();

        return view('admin.staff.show', [
            'user' => $user,
            'history' => $actor->can('audit.read') && $actor->hasUnscopedPermission('audit.read')
                ? AuditLog::query()->where(['target_type' => 'user', 'target_id' => $user->id])->where('action', 'like', 'roles.%')->latest('id')->limit(20)->get()
                : collect(),
            'assignable' => Role::query()->orderByDesc('priority')->get()
                ->filter(fn (Role $r): bool => $actor->isSuperAdmin() || $r->priority < $actor->rolePriority())->values(),
        ]);
    }

    public function assignRole(AssignRoleRequest $request, User $user, AssignRole $action): RedirectResponse
    {
        $data = $request->validated();
        /** @var User $actor */
        $actor = $request->user();

        $action->handle(
            $actor, $user,
            Role::query()->findOrFail((int) $data['role_id']),
            (string) $data['reason'],
            isset($data['expires_at']) ? CarbonImmutable::parse((string) $data['expires_at']) : null,
        );

        return back()->with('toast', __('admin.staff.assigned'));
    }

    public function revokeRole(ReasonRequest $request, User $user, Role $role, RevokeRole $action): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $action->handle($actor, $user, $role, (string) $request->validated('reason'));

        return back()->with('toast', __('admin.staff.revoked'));
    }
}
