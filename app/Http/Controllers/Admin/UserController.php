<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Account\AccountDeletion;
use App\Actions\Admin\SetUserSuspension;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReasonRequest;
use App\Http\Requests\Admin\SuspendUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSecurityEvent;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Kullanıcı yönetimi — docs/ROUTES.md §6.2, SRS FR-ADM-005/006. PII yalnızca users.read_pii ile. */
final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $users = User::query()
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($q)).'%';
                $query->where(fn ($w) => $w->where('username', 'like', $like)->orWhere('email', 'like', $like)->orWhere('ulid', mb_strtolower($q)));
            })
            ->when(UserStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'q' => $q, 'status' => $status, 'canPii' => $request->user()?->can('users.read_pii') ?? false]);
    }

    public function show(Request $request, User $user): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $user->load('roles:id,slug,name,priority,is_staff');

        $assignable = $actor->can('staff.manage')
            ? Role::query()->orderByDesc('priority')->get()->filter(fn (Role $r): bool => $actor->isSuperAdmin() || $r->priority < $actor->rolePriority())->values()
            : collect();

        return view('admin.users.show', [
            'user' => $user,
            'canPii' => $actor->can('users.read_pii'),
            'assignable' => $assignable,
            'branches' => $user->branches()->get(['branches.id', 'branches.name']),
        ]);
    }

    public function security(User $user): View
    {
        return view('admin.users.security', [
            'user' => $user,
            'events' => UserSecurityEvent::query()->where('user_id', $user->id)->latest('id')->paginate(30),
        ]);
    }

    public function status(SuspendUserRequest $request, User $user, SetUserSuspension $action): RedirectResponse
    {
        $data = $request->validated();
        /** @var User $actor */
        $actor = $request->user();

        $action->handle(
            $actor, $user,
            $data['action'] === 'suspend',
            (string) $data['reason'],
            isset($data['until']) ? CarbonImmutable::parse((string) $data['until']) : null,
        );

        return back()->with('toast', __('admin.saved'));
    }

    public function resendVerification(User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('toast', __('admin.users.verification_sent'));
    }

    /** KVKK talebiyle anonimleştirme — docs/ROUTES.md §6.2, DATABASE §13 (PC + gerekçe; hiyerarşi). */
    public function anonymize(ReasonRequest $request, User $user, AccountDeletion $deletion): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_if($user->id === $actor->id || ($user->isStaff() && ! $actor->outranks($user)), 403);

        $deletion->anonymize($user, $actor, (string) $request->validated('reason'));

        return back()->with('toast', 'Hesap anonimleştirildi.');
    }
}
