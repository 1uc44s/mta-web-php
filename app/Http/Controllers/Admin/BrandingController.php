<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpdateBranding;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandingRequest;
use App\Models\User;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** ACP → Marka: site adı, kısa ad, slogan, logo ve sekme ikonu — docs/ROUTES.md §6, DESIGN §2. */
final class BrandingController extends Controller
{
    public function __construct(private readonly UpdateBranding $branding) {}

    public function edit(Request $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('admin.branding.edit', [
            'name' => Brand::name(),
            'shortName' => Brand::shortName(),
            'tagline' => Brand::tagline(),
            'logoUrl' => Brand::logoUrl(),
            'iconUrl' => Brand::hasCustomIcon() ? Brand::iconUrl(192) : null,
            'canUpdate' => $actor->can('settings.update'),
        ]);
    }

    public function update(BrandingRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $changed = $this->branding->handle($actor, $request->text(), $request->upload('logo'), $request->upload('icon'));

        return redirect()->route('admin.branding')->with('toast', $changed > 0 ? __('admin.saved') : __('admin.no_changes'));
    }

    public function remove(Request $request, string $asset): RedirectResponse
    {
        abort_unless(in_array($asset, ['logo', 'icon'], true), 404);
        /** @var User $actor */
        $actor = $request->user();
        $this->branding->remove($actor, $asset);

        return redirect()->route('admin.branding')->with('toast', __('admin.branding.removed'));
    }
}
