<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Oyuncu paneli genel bakış (docs/SRS.md FR-UCP-001). Faz 3: iskelet; kartlar Faz 6'da. */
final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('panel.dashboard', ['user' => $request->user()]);
    }
}
