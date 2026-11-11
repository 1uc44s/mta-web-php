<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactStatusRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** İletişim mesajları — docs/ROUTES.md §6.9, DATABASE §10.16 (durumlar: new, read, replied, spam). */
final class ContactMessageController extends Controller
{
    public const STATUSES = ['new', 'read', 'replied', 'spam'];

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');

        return view('admin.contact.index', [
            'status' => $status,
            'messages' => ContactMessage::query()
                ->when(in_array($status, self::STATUSES, true), fn ($q) => $q->where('status', $status))
                ->latest('id')->paginate(30)->withQueryString(),
        ]);
    }

    public function show(Request $request, ContactMessage $message): View
    {
        if ($message->status === 'new') {
            $message->update(['status' => 'read', 'handled_by' => $request->user()?->getAuthIdentifier()]);
        }

        return view('admin.contact.show', ['message' => $message]);
    }

    public function status(ContactStatusRequest $request, ContactMessage $message): RedirectResponse
    {
        $message->update(['status' => $request->validated('status'), 'handled_by' => $request->user()?->getAuthIdentifier()]);

        return back()->with('toast', __('content_admin.saved'));
    }
}
