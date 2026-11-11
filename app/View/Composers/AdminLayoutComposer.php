<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * ACP menüsü — docs/ADMIN.md §9. Menü yalnızca görünürlüktür (FR-ADM-002); erişim kontrolü her rotada `can:` ile yapılır.
 * Henüz tanımlanmamış rotalar (sonraki fazlar) menüde gösterilmez.
 */
final class AdminLayoutComposer
{
    /** @var list<array{label: string, items: list<array{label: string, route: string, icon: string, can: string|null, match: string}>}> */
    private const MENU = [
        ['label' => 'admin.nav.overview', 'items' => [
            ['label' => 'admin.nav.overview', 'route' => 'admin.dashboard', 'icon' => 'gauge', 'can' => null, 'match' => 'admin.dashboard'],
        ]],
        ['label' => 'admin.nav.players', 'items' => [
            ['label' => 'admin.nav.users', 'route' => 'admin.users.index', 'icon' => 'users', 'can' => 'users.read', 'match' => 'admin.users.*'],
        ]],
        ['label' => 'admin.nav.team', 'items' => [
            ['label' => 'admin.nav.staff', 'route' => 'admin.staff.index', 'icon' => 'shield-user', 'can' => 'staff.read', 'match' => 'admin.staff.*'],
            ['label' => 'admin.nav.branches', 'route' => 'admin.branches.index', 'icon' => 'git-branch', 'can' => 'staff.read', 'match' => 'admin.branches.*'],
            ['label' => 'admin.nav.roles', 'route' => 'admin.roles.index', 'icon' => 'key-round', 'can' => 'roles.read', 'match' => 'admin.roles.*'],
        ]],
        ['label' => 'admin.nav.system', 'items' => [
            ['label' => 'admin.nav.settings', 'route' => 'admin.settings.index', 'icon' => 'sliders-horizontal', 'can' => 'settings.read', 'match' => 'admin.settings.*'],
            ['label' => 'admin.nav.health', 'route' => 'admin.system.health', 'icon' => 'activity', 'can' => 'settings.read', 'match' => 'admin.system.*'],
            ['label' => 'admin.nav.audit', 'route' => 'admin.audit-logs.index', 'icon' => 'scroll-text', 'can' => 'audit.read', 'match' => 'admin.audit-logs.*'],
            ['label' => 'admin.nav.activity', 'route' => 'admin.activity-logs.index', 'icon' => 'eye', 'can' => 'activity.read', 'match' => 'admin.activity-logs.*'],
        ]],
    ];

    public function compose(View $view): void
    {
        $user = request()->user();
        $menu = [];

        foreach (self::MENU as $group) {
            $items = [];
            foreach ($group['items'] as $item) {
                if (! Route::has($item['route'])) {
                    continue;
                }
                if ($item['can'] !== null && ! ($user instanceof User && $user->can($item['can']))) {
                    continue;
                }
                $items[] = [
                    'label' => __($item['label']),
                    'href' => route($item['route']),
                    'icon' => $item['icon'],
                    'active' => request()->routeIs($item['match']),
                ];
            }
            if ($items !== []) {
                $menu[] = ['label' => __($group['label']), 'items' => $items];
            }
        }

        $view->with('adminMenu', $menu)->with('adminUser', $user);
    }
}
