<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * GET /panel/security/two-factor — kurulum sihirbazı (docs/ROUTES.md §5, SRS FR-AUTH-016/017).
 * İşlemler Fortify rotalarıyla yapılır (enable → QR + anahtar → onay kodu → kurtarma kodları). Tümü parola onayı ister.
 * QR, data: URI'li <img> olarak basılır (CSP img-src data: izinli; ham SVG HTML'e gömülmez).
 */
final class TwoFactorController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $pending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;
        $confirmed = $user->hasTwoFactorEnabled();
        $justConfirmed = session('status') === 'two-factor-authentication-confirmed';
        $showCodes = $confirmed && ($justConfirmed || session('status') === 'recovery-codes-generated');

        return view('panel.two-factor', [
            'user' => $user,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'qr' => $pending ? 'data:image/svg+xml;base64,'.base64_encode($user->twoFactorQrCodeSvg()) : null,
            'secret' => $pending ? decrypt((string) $user->two_factor_secret) : null,
            'recoveryCodes' => $showCodes ? $user->recoveryCodes() : [],
            'isStaff' => $user->isStaff(),
        ]);
    }
}
