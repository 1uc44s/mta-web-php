<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Applications\ApplicationWorkflow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecisionRequest;
use App\Http\Requests\Admin\StaffMessageRequest;
use App\Models\Application;
use App\Models\ApplicationType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Başvuru inceleme — docs/ROUTES.md §6.5, SRS FR-APP-005/006. Kapsam: `applications.all_branches` veya türün branşına
 * üyelik; kapsam dışı başvuru 404 (varlık ifşa edilmez). Taslaklar inceleme kuyruğunda görünmez.
 */
final class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $actor = $this->actor($request);
        $filter = (string) $request->query('filter', 'open');
        $type = $request->integer('type');

        return view('admin.applications.index', [
            'filter' => $filter,
            'typeId' => $type,
            'types' => ApplicationType::query()
                ->when(! $actor->hasUnscopedPermission('applications.all_branches'), fn ($q) => $q->whereIn('branch_id', $actor->activeBranchIds()))
                ->orderBy('name')->get(['id', 'name']),
            'applications' => Application::query()->reviewableBy($actor)->where('status', '!=', 'draft')
                ->with(['type:id,name', 'user:id,username', 'assignee:id,username'])
                ->when($filter === 'open', fn ($q) => $q->whereIn('status', ['submitted', 'in_review', 'needs_info']))
                ->when($filter === 'mine', fn ($q) => $q->where('assigned_to', $actor->id)->whereIn('status', ['submitted', 'in_review', 'needs_info']))
                ->when($filter === 'decided', fn ($q) => $q->whereIn('status', ['approved', 'rejected']))
                ->when($type > 0, fn ($q) => $q->where('application_type_id', $type))
                ->latest('submitted_at')->paginate(25)->withQueryString(),
        ]);
    }

    public function show(Request $request, string $application): View
    {
        $model = $this->scoped($request, $application);

        return view('admin.applications.show', [
            'application' => $model->load(['type', 'user:id,username,ulid,created_at', 'character:id,ulid,name', 'answers', 'messages.author:id,username', 'assignee:id,username']),
            'history' => $this->workflow->staffHistory($model),
            'canReview' => $this->actor($request)->hasPermission('applications.review'),
        ]);
    }

    public function assign(Request $request, string $application): RedirectResponse
    {
        $this->workflow->assign($this->scoped($request, $application), $request->boolean('unassign') ? null : $this->actor($request));

        return back()->with('status', __('applications.admin.done'));
    }

    public function status(DecisionRequest $request, string $application): RedirectResponse
    {
        $data = $request->validated();
        $this->workflow->transition($this->actor($request), $this->scoped($request, $application), (string) $data['status'], $data['note_public'] ?? null, $data['note_internal'] ?? null);

        return back()->with('status', __('applications.admin.done'));
    }

    public function message(StaffMessageRequest $request, string $application): RedirectResponse
    {
        $data = $request->validated();
        $this->workflow->staffMessage($this->actor($request), $this->scoped($request, $application), (string) $data['body'], (string) $data['visibility']);

        return back()->with('status', __('applications.admin.done'));
    }

    private function scoped(Request $request, string $ulid): Application
    {
        return Application::query()->reviewableBy($this->actor($request))->where('status', '!=', 'draft')->where('ulid', $ulid)->firstOrFail();
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
