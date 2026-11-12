<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Actions\GameAccounts\LinkCodes;
use App\Actions\GameAccounts\ManageDevices;
use App\Http\Controllers\Controller;
use App\Models\GameAccountLink;
use App\Models\MtaAccountDevice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cihazlarım — docs/ROUTES.md §5.3 (R2, F:devices), SRS FR-ACC-010. Yalnızca aktif bağlı hesapların cihazları;
 * ham serial hiçbir zaman gösterilmez (parmak izi = HMAC'in ilk 8 karakteri).
 */
final class DeviceController extends Controller
{
    private const FRESH_CODE_KEY = 'device_link_code.fresh';

    public function __construct(private readonly LinkCodes $codes, private readonly ManageDevices $devices) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);
        $fresh = $request->session()->pull(self::FRESH_CODE_KEY);

        return view('panel.devices', [
            'devices' => MtaAccountDevice::query()->whereIn('mta_account_id', GameAccountLink::query()->where('user_id', $user->id)
                ->whereIn('status', GameAccountLink::ACTIVE)->select('mta_account_id'))->with('account:id,account_name')->latest('id')->get(),
            'freshCode' => is_string($fresh) ? $fresh : null,
            'codeStatus' => $this->codes->status($user, LinkCodes::DEVICE_PURPOSE),
            'hasLink' => $this->codes->activeLinkCount($user) > 0,
        ]);
    }

    public function linkCode(Request $request): RedirectResponse
    {
        $issued = $this->codes->issue($this->user($request), LinkCodes::DEVICE_PURPOSE);
        $request->session()->put(self::FRESH_CODE_KEY, $issued['code']);

        return redirect()->route('panel.devices.index');
    }

    public function revoke(Request $request, string $device): RedirectResponse
    {
        $this->devices->revokeByOwner($this->user($request), MtaAccountDevice::query()->findOrFail((int) $device));

        return redirect()->route('panel.devices.index')->with('status', __('game.device.revoked'));
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
