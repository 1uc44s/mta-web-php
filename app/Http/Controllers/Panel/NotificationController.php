<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Content\AnnouncementFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Bildirimler — docs/ROUTES.md §5.1, SRS FR-UCP-002/003. */
final class NotificationController extends Controller
{
    public function index(Request $request, AnnouncementFeed $feed): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('panel.notifications', [
            'notifications' => $user->notifications()->paginate(20),
            'announcements' => $feed->forUser($user, 10),
        ]);
    }

    /** FR-UCP-003: `{unread}` + ETag (304 destekli). Sayaç polling'i sekme görünürken 60 sn'de bir çağırır. */
    public function count(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $unread = $user->unreadNotifications()->count();
        $etag = '"n-'.$unread.'"';

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->json(null, 304)->setEtag(trim($etag, '"'));
        }

        return response()->json(['success' => true, 'data' => ['unread' => $unread]])->setEtag(trim($etag, '"'));
    }

    /** Sahiplik: yalnızca kendi bildirimleri (başkasının UUID'si → 404). */
    public function read(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notification = $user->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = (string) ($notification->data['url'] ?? '');

        return $url !== '' && str_starts_with($url, (string) config('app.url')) ? redirect()->to($url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('toast', 'Tüm bildirimler okundu olarak işaretlendi.');
    }
}
