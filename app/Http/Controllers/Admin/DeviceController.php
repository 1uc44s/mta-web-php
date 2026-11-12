<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\GameAccounts\ManageDevices;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReasonRequest;
use App\Models\MtaAccountDevice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Cihaz talepleri — docs/ROUTES.md §6.6 (R2), SRS FR-ACC-010. Serial HMAC'i yalnızca kısa parmak izi olarak görünür. */
final class DeviceController extends Controller
{
    public function __construct(private readonly ManageDevices $devices) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'pending_approval');

        return view('admin.devices.index', [
            'status' => $status,
            'devices' => MtaAccountDevice::query()->with('account:id,ulid,account_name')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))->latest('id')->paginate(30)->withQueryString(),
        ]);
    }

    public function approve(ReasonRequest $request, string $device): RedirectResponse
    {
        $this->devices->approve($this->actor($request), MtaAccountDevice::query()->findOrFail((int) $device), (string) $request->validated('reason'));

        return back()->with('status', __('game.admin.done'));
    }

    public function reject(ReasonRequest $request, string $device): RedirectResponse
    {
        $this->devices->reject($this->actor($request), MtaAccountDevice::query()->findOrFail((int) $device), (string) $request->validated('reason'));

        return back()->with('status', __('game.admin.done'));
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
