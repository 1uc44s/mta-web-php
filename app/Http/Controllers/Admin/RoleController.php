<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReasonRequest;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Roller ve izinler — docs/ROUTES.md §6.10. Sistem rolleri salt okunurdur (izinleri config/roles.php'den seed edilir);
 * ACP yalnızca özel (kozmetik veya personel) roller oluşturur/düzenler. roles.manage yalnızca süper admindedir.
 */
final class RoleController extends Controller
{
    public function __construct(private readonly SaveRole $save) {}

    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->withCount(['users', 'permissions'])->orderByDesc('priority')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new Role(['is_staff' => true, 'priority' => 20]), 'granted' => [], 'groups' => $this->groups()]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $role = $this->save->create($request->validated());

        return to_route('admin.roles.edit', $role)->with('toast', __('admin.roles.created'));
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.form', [
            'role' => $role,
            'granted' => $role->isSuperAdmin() ? array_keys((array) config('permissions')) : $role->permissions()->pluck('key')->all(),
            'scoped' => (array) config('roles.'.$role->slug.'.scoped', []),
            'groups' => $this->groups(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $this->save->update($role, $request->validated());

        return back()->with('toast', __('admin.roles.updated'));
    }

    public function destroy(ReasonRequest $request, Role $role): RedirectResponse
    {
        $this->save->delete($role, (string) $request->validated('reason'));

        return to_route('admin.roles.index')->with('toast', __('admin.roles.deleted'));
    }

    /** @return array<string, list<array{key: string, description: string, sensitive: bool}>> */
    private function groups(): array
    {
        $groups = [];
        foreach ((array) config('permissions') as $key => $meta) {
            $groups[$meta['group']][] = ['key' => $key, 'description' => $meta['description'], 'sensitive' => $meta['sensitive']];
        }

        return $groups;
    }
}
