<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Support\TicketWorkflow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketActionRequest;
use App\Http\Requests\Panel\TicketMessageRequest;
use App\Models\Branch;
use App\Models\SupportAttachment;
use App\Models\SupportCategory;
use App\Models\SupportMacro;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Messaging\TemplateRenderer;
use App\Services\Messaging\TemplateSyntaxException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ACP destek — docs/ROUTES.md §6.4, SRS FR-SUP-005..010. Branş kapsamı her uçta: kapsam dışı ticket 404.
 */
final class SupportController extends Controller
{
    public function __construct(private readonly TicketWorkflow $workflow) {}

    public function index(Request $request): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $status = (string) $request->query('status', 'active');
        $assigned = (string) $request->query('assigned', '');
        $q = trim((string) $request->query('q', ''));

        $tickets = SupportTicket::query()->visibleTo($actor)
            ->with(['category:id,name', 'branch:id,name', 'assignee:id,username', 'user:id,username'])
            ->when($status === 'active', fn ($b) => $b->whereIn('status', SupportTicket::ACTIVE))
            ->when(in_array($status, SupportTicket::STATUSES, true), fn ($b) => $b->where('status', $status))
            ->when($assigned === 'me', fn ($b) => $b->where('assigned_to', $actor->id))
            ->when($assigned === 'none', fn ($b) => $b->whereNull('assigned_to'))
            ->when($request->filled('priority'), fn ($b) => $b->where('priority', (string) $request->query('priority')))
            ->when($request->filled('category'), fn ($b) => $b->where('category_id', (int) $request->query('category')))
            ->when($q !== '', function ($b) use ($q): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
                $b->where(fn ($w) => $w->where('number', $q)->orWhere('subject', 'like', $like));
            })
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->latest('last_message_at')
            ->paginate(30)->withQueryString();

        return view('admin.support.index', [
            'tickets' => $tickets, 'status' => $status, 'assigned' => $assigned, 'q' => $q,
            'categories' => SupportCategory::query()->orderBy('sort')->pluck('name', 'id'),
            // Gerçek zaman (Faz 10): dinlenecek branş kanalları — kanal yetkisi routes/channels.php'de yeniden doğrulanır.
            'realtimeBranches' => $actor->can('support.all_branches')
                ? Branch::query()->pluck('id')->map(fn ($id): int => (int) $id)->all()
                : $actor->activeBranchIds(),
        ]);
    }

    /** JSON sayaçlar (T:poll) — kuyruk rozetleri. */
    public function counters(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $base = SupportTicket::query()->visibleTo($actor)->whereIn('status', SupportTicket::ACTIVE);

        return response()->json(['success' => true, 'data' => [
            'unassigned' => (clone $base)->whereNull('assigned_to')->count(),
            'mine' => (clone $base)->where('assigned_to', $actor->id)->count(),
            'waiting_for_staff' => (clone $base)->whereIn('status', ['open', 'waiting_for_staff'])->count(),
        ]]);
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->scope($request, $ticket);

        if ($ticket->staff_unread) {
            $ticket->forceFill(['staff_unread' => false])->saveQuietly();
        }

        return view('admin.support.show', [
            'ticket' => $ticket->load(['category:id,name', 'branch:id,name', 'assignee:id,username', 'user:id,username,ulid,status']),
            'messages' => $ticket->messages()->with(['author:id,username,status', 'attachments'])->get(),
            'events' => $ticket->events()->with('actor:id,username')->get(),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'macros' => SupportMacro::query()->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $ticket->branch_id))
                ->orderBy('title')->get(['id', 'title', 'body']),
            'macroContext' => [
                'player.username' => $ticket->user->username, 'ticket.number' => $ticket->number,
                'ticket.subject' => $ticket->subject, 'staff.username' => $request->user()?->username,
            ],
        ]);
    }

    public function message(TicketMessageRequest $request, SupportTicket $ticket, TemplateRenderer $renderer): RedirectResponse
    {
        $this->scope($request, $ticket);
        /** @var User $actor */
        $actor = $request->user();
        $internal = $request->validated('visibility') === 'internal';

        if ($internal && ! $actor->can('support.internal_note')) {
            throw ValidationException::withMessages(['visibility' => __('support.errors.internal_permission')]);
        }

        // FR-SUP-009: makro değişkenleri şablon motoruyla doldurulur ({{ player.username }} …).
        try {
            $body = $renderer->render((string) $request->validated('body'), [
                'player.username' => $ticket->user->username, 'ticket.number' => $ticket->number,
                'ticket.subject' => $ticket->subject, 'staff.username' => $actor->username,
            ], SupportMacro::VARIABLES, 'text');
        } catch (TemplateSyntaxException $e) {
            throw ValidationException::withMessages(['body' => $e->getMessage()]);
        }

        $this->workflow->addStaffMessage($ticket, $actor, $body, $internal);

        return back()->with('toast', __('support.sent'));
    }

    public function status(TicketActionRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->scope($request, $ticket);
        /** @var User $actor */
        $actor = $request->user();
        $status = (string) $request->validated('status');
        abort_if($status === 'closed' && ! $actor->can('support.close'), 403);

        $this->workflow->changeStatus($ticket, $actor, $status);

        return back()->with('toast', __('admin.saved'));
    }

    public function assign(TicketActionRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->scope($request, $ticket);
        /** @var User $actor */
        $actor = $request->user();
        $identifier = mb_strtolower(trim((string) $request->validated('assignee')));
        $assignee = $identifier === '' ? null : User::query()->where('username', $identifier)->orWhere('email', $identifier)->first();

        if ($identifier !== '' && $assignee === null) {
            throw ValidationException::withMessages(['assignee' => __('admin.staff.errors.user_not_found')]);
        }

        $this->workflow->assign($ticket, $actor, $assignee);

        return back()->with('toast', __('admin.saved'));
    }

    public function claim(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->scope($request, $ticket);
        /** @var User $actor */
        $actor = $request->user();
        $this->workflow->assign($ticket, $actor, $actor);

        return back()->with('toast', __('admin.saved'));
    }

    public function priority(TicketActionRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->scope($request, $ticket);
        /** @var User $actor */
        $actor = $request->user();
        $this->workflow->changePriority($ticket, $actor, (string) $request->validated('priority'));

        return back()->with('toast', __('admin.saved'));
    }

    public function escalate(TicketActionRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->scope($request, $ticket);
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $this->workflow->escalate($ticket, $actor, Branch::query()->findOrFail((int) $data['branch_id']), (string) $data['note']);

        // İletilen ticket artık başka branşta olabilir: kuyruğa dön.
        return to_route('admin.support.index')->with('toast', __('admin.saved'));
    }

    public function reopenDecision(TicketActionRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->scope($request, $ticket);
        /** @var User $actor */
        $actor = $request->user();
        $this->workflow->decideReopen($ticket, $actor, $request->validated('decision') === 'approve');

        return back()->with('toast', __('admin.saved'));
    }

    public function attachment(Request $request, SupportAttachment $attachment): StreamedResponse
    {
        $this->scope($request, $attachment->ticket);

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** ADMIN §5.2: kapsam dışı ticket — varlık ifşa edilmez (404). */
    private function scope(Request $request, SupportTicket $ticket): void
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless(SupportTicket::query()->visibleTo($actor)->whereKey($ticket->id)->exists(), 404);
    }
}
