<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Actions\Panel\AcknowledgeRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Content\AnnouncementFeed;
use App\Services\Settings\SettingsRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Oyuncu paneli genel bakış (docs/SRS.md FR-UCP-001). Faz 5: kural onayı bandı (CMS §5.3) ve panel duyuruları.
 * Tam kart düzeni Faz 6'da.
 */
final class DashboardController extends Controller
{
    public function __invoke(Request $request, AcknowledgeRules $rules, AnnouncementFeed $feed, SettingsRepository $settings): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('panel.dashboard', [
            'user' => $user,
            'rulesPending' => $rules->needsAcknowledgement($user),
            'rulesVersion' => $rules->currentVersion(),
            'rulesNote' => $settings->get('rules.change_note'),
            'announcements' => $feed->forUser($user),
        ]);
    }
}
