<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpdateSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sistem ayarları — docs/ROUTES.md §6.11, ADMIN §7, SRS FR-ADM-009. */
final class SettingsController extends Controller
{
    public function __construct(private readonly SettingsRepository $settings) {}

    public function index(): View
    {
        $groups = [];
        foreach ($this->settings->catalog() as $key => $meta) {
            $groups[$meta['group']]['count'] = ($groups[$meta['group']]['count'] ?? 0) + 1;
            $groups[$meta['group']]['super'] = ($groups[$meta['group']]['super'] ?? false) || ($meta['super'] ?? false);
        }

        return view('admin.settings.index', ['groups' => $groups]);
    }

    public function edit(Request $request, string $group): View
    {
        $items = array_filter($this->settings->catalog(), fn (array $m): bool => $m['group'] === $group);
        abort_if($items === [], 404);

        /** @var User $actor */
        $actor = $request->user();

        $fields = [];
        foreach ($items as $key => $meta) {
            $value = $this->settings->get($key);
            $fields[] = [
                'key' => $key,
                'name' => SettingsRequest::field($key),
                'type' => $meta['type'],
                'value' => $meta['type'] === 'json' && $value !== null ? (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $value,
                'locked' => ($meta['super'] ?? false) && ! $actor->isSuperAdmin(),
                'super' => (bool) ($meta['super'] ?? false),
            ];
        }

        return view('admin.settings.edit', ['group' => $group, 'fields' => $fields, 'canUpdate' => $actor->can('settings.update')]);
    }

    public function update(SettingsRequest $request, string $group, UpdateSettings $action): RedirectResponse
    {
        abort_if($request->groupCatalog() === [], 404);
        /** @var User $actor */
        $actor = $request->user();

        $values = $request->settings();
        if (! $actor->isSuperAdmin()) {
            // Kilitli (süper-only) alanlar formda devre dışıdır; gönderilmemiş varsayılır ve dokunulmaz.
            $values = array_filter($values, fn (string $key): bool => ! ($request->groupCatalog()[$key]['super'] ?? false), ARRAY_FILTER_USE_KEY);
        }

        $changed = $action->handle($actor, $group, $values);

        return back()->with('toast', $changed > 0 ? __('admin.saved') : __('admin.no_changes'));
    }
}
