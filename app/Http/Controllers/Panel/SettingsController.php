<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\NotificationPreferencesRequest;
use App\Http\Requests\Panel\PreferencesRequest;
use App\Models\User;
use App\Services\Notifications\NotificationPreferences;
use App\Services\Push\PushSubscriptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

/** Ayarlar — docs/ROUTES.md §5.1, SRS FR-UCP-004 (tema/efekt kullanıcıda + cookie), DATABASE §3.11. */
final class SettingsController extends Controller
{
    public function __construct(private readonly NotificationPreferences $preferences, private readonly PushSubscriptions $push) {}

    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('panel.settings', ['user' => $user, 'prefs' => $this->preferences->matrix($user), 'pushAvailable' => $this->push->available()]);
    }

    public function preferences(PreferencesRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $user->forceFill(['theme' => $data['theme'], 'reduced_effects' => (bool) $data['reduced_effects']])->save();

        $year = 60 * 24 * 365;
        Cookie::queue(Cookie::make('mrp_theme', (string) $data['theme'], $year, '/', null, null, false, false, 'Lax'));
        Cookie::queue(Cookie::make('mrp_effects', $data['reduced_effects'] ? 'low' : 'full', $year, '/', null, null, false, false, 'Lax'));

        return back()->with('toast', 'Görünüm tercihlerin kaydedildi.');
    }

    public function notifications(NotificationPreferencesRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->preferences->save($user, $request->matrix());

        return back()->with('toast', 'Bildirim tercihlerin kaydedildi.');
    }
}
