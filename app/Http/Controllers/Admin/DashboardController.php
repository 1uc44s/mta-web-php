<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** GET /admin — docs/ROUTES.md §6.1. Widget'lar izne göre filtrelenir; KPI'lar canlı sorgu (FR-ADM-010). */
final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $kpis = [];
        if ($user->can('users.read')) {
            $kpis['users'] = User::query()->count();
            $kpis['new_users'] = User::query()->where('created_at', '>=', now()->subDay())->count();
            $kpis['unverified'] = User::query()->whereNull('email_verified_at')->count();
            $kpis['suspended'] = User::query()->where('status', UserStatus::Suspended->value)->count();
        }
        if ($user->can('staff.read')) {
            $kpis['staff'] = User::query()->whereHas('roles', fn ($q) => $q->where('is_staff', true))->count();
        }
        if ($user->can('contact.read')) {
            $kpis['contact'] = ContactMessage::query()->where('status', 'new')->count();
        }

        $superAdmins = User::query()->whereHas('roles', fn ($q) => $q->where('slug', Role::SUPER_ADMIN))->count();

        $recentAudit = $user->can('audit.read') && $user->hasUnscopedPermission('audit.read')
            ? AuditLog::query()->with('actor:id,username,ulid')->whereIn('severity', ['warning', 'critical'])->latest('id')->limit(8)->get()
            : collect();

        return view('admin.dashboard', [
            'user' => $user,
            'kpis' => $kpis,
            'singleSuperAdmin' => $user->isSuperAdmin() && $superAdmins === 1,
            'recentAudit' => $recentAudit,
            'branches' => $user->branches()->get(['branches.id', 'branches.name', 'branches.slug']),
        ]);
    }
}
