<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Actions\Applications\ApplicationWorkflow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ApplicationFormRequest;
use App\Http\Requests\Panel\MessageBodyRequest;
use App\Models\Application;
use App\Models\ApplicationType;
use App\Models\Character;
use App\Models\User;
use App\Support\ApplicationForms\ApplicationForms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Oyuncu başvuruları — docs/ROUTES.md §5.5, SRS FR-APP-001..004. Başkasının başvurusu 404 (IDOR); iç mesajlar ve iç
 * notlar hiçbir oyuncu sorgusunda yüklenmez. Taslak, `show` adresinde form olarak açılır.
 */
final class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);

        return view('panel.applications.index', [
            'applications' => Application::query()->where('user_id', $user->id)->with('type:id,name,slug,kind')->latest('id')->get(),
            'types' => ApplicationType::query()->where('is_open', true)->orderBy('sort')->orderBy('name')->get()
                ->map(fn (ApplicationType $t): array => ['type' => $t, 'blocked' => $this->workflow->eligibility($user, $t)]),
        ]);
    }

    public function create(Request $request, string $type): View
    {
        $user = $this->user($request);
        $model = ApplicationType::query()->where('slug', $type)->firstOrFail();

        return $this->form($user, $model, null, $this->workflow->eligibility($user, $model));
    }

    public function store(ApplicationFormRequest $request, string $type): RedirectResponse
    {
        $model = ApplicationType::query()->where('slug', $type)->firstOrFail();
        $data = $request->validated();
        $submit = $data['action'] === 'submit';

        $application = $this->workflow->save($this->user($request), $model, null, (array) ($data['answers'] ?? []), $data['character'] ?? null, $submit);

        return redirect()->route('panel.applications.show', $application)->with('status', __($submit ? 'applications.submitted' : 'applications.saved'));
    }

    public function show(Request $request, string $application): View
    {
        $user = $this->user($request);
        $model = $this->owned($request, $application);

        if ($model->status === 'draft') {
            return $this->form($user, $model->type, $model, null);
        }

        return view('panel.applications.show', [
            'application' => $model->load(['type:id,name,kind', 'character:id,name', 'answers', 'publicMessages.author:id,username']),
            'history' => $this->workflow->publicHistory($model),
        ]);
    }

    public function update(ApplicationFormRequest $request, string $application): RedirectResponse
    {
        $model = $this->owned($request, $application);
        $data = $request->validated();
        $submit = $data['action'] === 'submit';

        $this->workflow->save($this->user($request), $model->type, $model, (array) ($data['answers'] ?? []), $data['character'] ?? null, $submit);

        return redirect()->route('panel.applications.show', $model)->with('status', __($submit ? 'applications.submitted' : 'applications.saved'));
    }

    public function message(MessageBodyRequest $request, string $application): RedirectResponse
    {
        $this->workflow->playerReply($this->user($request), $this->owned($request, $application), (string) $request->validated('body'));

        return back()->with('status', __('applications.replied'));
    }

    public function cancel(Request $request, string $application): RedirectResponse
    {
        $this->workflow->cancel($this->user($request), $this->owned($request, $application));

        return redirect()->route('panel.applications.index')->with('status', __('applications.cancelled'));
    }

    private function form(User $user, ApplicationType $type, ?Application $application, ?string $blocked): View
    {
        return view('panel.applications.form', [
            'type' => $type,
            'application' => $application,
            'fields' => ApplicationForms::fields($type->form_key),
            'answers' => $application?->answers()->pluck('answer', 'field_key')->all() ?? [],
            'blocked' => $blocked,
            'characters' => Character::query()->ownedBy($user)->orderBy('name')->get(['id', 'ulid', 'name']),
        ]);
    }

    private function owned(Request $request, string $ulid): Application
    {
        return Application::query()->where('user_id', $this->user($request)->id)->where('ulid', $ulid)->firstOrFail();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
