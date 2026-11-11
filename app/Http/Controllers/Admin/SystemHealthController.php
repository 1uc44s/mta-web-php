<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\System\HealthProbe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sistem sağlığı — docs/ROUTES.md §6.1. */
final class SystemHealthController extends Controller
{
    public function __construct(private readonly HealthProbe $probe) {}

    public function show(Request $request, AuditLogger $audit): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('admin.system.health', [
            'info' => $this->probe->info(),
            'debug' => (bool) config('app.debug'),
            'pendingJobs' => $this->probe->pendingJobs(),
            'failedJobs' => $this->probe->failedJobs(),
            'chainBroken' => $actor->isSuperAdmin() ? $audit->verify() : false,
        ]);
    }

    public function retryFailedJob(string $uuid): RedirectResponse
    {
        abort_unless($this->probe->retry($uuid), 404);

        return back()->with('toast', __('admin.health.retried'));
    }
}
