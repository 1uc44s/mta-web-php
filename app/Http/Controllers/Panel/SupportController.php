<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Actions\Support\TicketWorkflow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\TicketMessageRequest;
use App\Http\Requests\Panel\TicketStoreRequest;
use App\Models\SupportAttachment;
use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Oyuncu destek ekranları — docs/ROUTES.md §5.4, SRS FR-SUP-001..006, 011.
 * Sahiplik: başkasının ticket ULID'si 404 döner (IDOR — varlık ifşa edilmez). İç notlar hiçbir sorguda yüklenmez.
 */
final class SupportController extends Controller
{
    public function __construct(private readonly TicketWorkflow $workflow) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $filter = (string) $request->query('status', 'open');

        return view('panel.support.index', [
            'filter' => $filter,
            'tickets' => SupportTicket::query()->where('user_id', $user->id)->with('category:id,name')
                ->when($filter === 'open', fn ($q) => $q->whereIn('status', [...SupportTicket::ACTIVE, 'resolved']))
                ->when($filter === 'closed', fn ($q) => $q->where('status', 'closed'))
                ->latest('updated_at')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('panel.support.create', [
            'categories' => SupportCategory::query()->active()->get(['id', 'name', 'description']),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(TicketStoreRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $ticket = $this->workflow->create(
            $user,
            SupportCategory::query()->findOrFail((int) $data['category_id']),
            (string) $data['subject'],
            (string) $data['body'],
            (string) $data['idempotency_key'],
            $request->attachmentFiles(),
        );

        return to_route('panel.support.show', $ticket)->with('toast', __('support.created'));
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->own($request, $ticket);

        if ($ticket->player_unread) {
            $ticket->forceFill(['player_unread' => false])->saveQuietly(); // ROUTES §5.4: görünüm durumu, audit gerektirmez
        }

        return view('panel.support.show', [
            'ticket' => $ticket->load('category:id,name'),
            'messages' => $ticket->publicMessages()->with(['author:id,username,status', 'attachments'])->get(),
        ]);
    }

    public function message(TicketMessageRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->own($request, $ticket);
        /** @var User $user */
        $user = $request->user();
        $this->workflow->addPlayerMessage($ticket, $user, (string) $request->validated('body'), $request->attachmentFiles());

        return back()->with('toast', __('support.sent'));
    }

    public function close(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->own($request, $ticket);
        /** @var User $user */
        $user = $request->user();
        $this->workflow->closeByPlayer($ticket, $user);

        return back()->with('toast', __('support.closed_note'));
    }

    public function reopenRequest(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->own($request, $ticket);
        /** @var User $user */
        $user = $request->user();
        $this->workflow->requestReopen($ticket, $user);

        return back()->with('toast', __('support.reopen_requested'));
    }

    /** Ek indirme: ticket sahibi + mesaj public (iç not ekleri asla). `attachment` + nosniff. */
    public function attachment(Request $request, SupportAttachment $attachment): StreamedResponse
    {
        $attachment->load(['ticket', 'message']);
        abort_unless($attachment->ticket->user_id === $request->user()?->getAuthIdentifier() && ! $attachment->message->isInternal(), 404);

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function own(Request $request, SupportTicket $ticket): void
    {
        abort_unless($ticket->user_id === $request->user()?->getAuthIdentifier(), 404);
    }
}
