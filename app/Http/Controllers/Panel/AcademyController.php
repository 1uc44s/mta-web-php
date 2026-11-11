<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Actions\Panel\AcknowledgeRules;
use App\Actions\Panel\CompleteAcademyTopic;
use App\Http\Controllers\Controller;
use App\Models\AcademyTopic;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Panel: Akademi ilerlemesi, kural onayı, duyuru okundu — docs/ROUTES.md §5.3, CMS §5.2–5.3. */
final class AcademyController extends Controller
{
    public function index(Request $request, CompleteAcademyTopic $academy): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('panel.academy', ['progress' => $academy->progress($user)]);
    }

    public function complete(Request $request, AcademyTopic $topic, CompleteAcademyTopic $academy): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $academy->handle($user, $topic);

        return back()->with('toast', 'Konu tamamlandı olarak işaretlendi.');
    }

    public function acknowledgeRules(Request $request, AcknowledgeRules $rules): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $rules->handle($user, $request->ip());

        return back()->with('toast', 'Kuralları onayladın. Teşekkürler!');
    }

    public function readAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($announcement->isPublished(), 404);
        $announcement->readers()->syncWithoutDetaching([$user->id => ['read_at' => now()]]);

        return back();
    }
}
