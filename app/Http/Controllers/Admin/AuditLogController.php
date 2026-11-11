<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Audit log — docs/ROUTES.md §6.11, SRS FR-ADM-008. Kapsamlı audit.read (finance_manager) yalnızca
 * wallet.* / payments.* / refunds.* eylemlerini görür (ADMIN §3.6). Zincir doğrulaması yalnızca süper admine gösterilir.
 */
final class AuditLogController extends Controller
{
    private const SCOPED_PREFIXES = ['wallet.', 'payments.', 'refunds.'];

    public function index(Request $request, AuditLogger $logger): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $action = trim((string) $request->query('action', ''));
        $severity = (string) $request->query('severity', '');

        $logs = $this->scoped($actor)
            ->with('actor:id,username,ulid')
            ->when($action !== '', fn ($q) => $q->where('action', 'like', str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $action).'%'))
            ->when(in_array($severity, ['info', 'warning', 'critical'], true), fn ($q) => $q->where('severity', $severity))
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        $chain = null;
        if ($actor->isSuperAdmin()) {
            $broken = $logger->verify();
            $chain = ['ok' => $broken === null, 'broken' => $broken, 'count' => AuditLog::query()->count()];
        }

        return view('admin.audit.index', ['logs' => $logs, 'action' => $action, 'severity' => $severity, 'chain' => $chain]);
    }

    public function show(Request $request, string $log): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $entry = $this->scoped($actor)->with('actor:id,username,ulid')->where('ulid', $log)->firstOrFail();

        return view('admin.audit.show', ['log' => $entry]);
    }

    /** @return Builder<AuditLog> */
    private function scoped(User $actor): Builder
    {
        $query = AuditLog::query();

        if (! $actor->hasUnscopedPermission('audit.read')) {
            $query->where(function (Builder $q): void {
                foreach (self::SCOPED_PREFIXES as $prefix) {
                    $q->orWhere('action', 'like', $prefix.'%');
                }
            });
        }

        return $query;
    }
}
