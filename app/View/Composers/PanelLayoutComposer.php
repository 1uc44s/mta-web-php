<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\User;
use App\Services\Bridge\ServerStatusResolver;
use Illuminate\View\View;

/**
 * Oyuncu paneli menüsü ve üst bar verisi — docs/DESIGN.md §7.2. Mobilde alt sekme çubuğu: Genel, Destek, Akademi,
 * Bildirimler, Menü. Henüz olmayan modüller (oyun hesabı, karakterler — Faz 7) menüde gösterilmez.
 */
final class PanelLayoutComposer
{
    public function __construct(private readonly ServerStatusResolver $status) {}

    public function compose(View $view): void
    {
        /** @var User|null $user */
        $user = request()->user();

        $items = [
            ['label' => 'Genel bakış', 'route' => 'panel.dashboard', 'icon' => 'layout-dashboard', 'match' => 'panel.dashboard', 'tab' => true],
            ['label' => 'Oyun hesaplarım', 'route' => 'panel.game-accounts.index', 'icon' => 'gamepad-2', 'match' => 'panel.game-accounts.*', 'tab' => false],
            ['label' => 'Karakterlerim', 'route' => 'panel.characters.index', 'icon' => 'users', 'match' => 'panel.characters.*', 'tab' => false],
            ['label' => 'Destek', 'route' => 'panel.support.index', 'icon' => 'life-buoy', 'match' => 'panel.support.*', 'tab' => true],
            ['label' => 'Akademi', 'route' => 'panel.academy', 'icon' => 'graduation-cap', 'match' => 'panel.academy', 'tab' => true],
            ['label' => 'Bildirimler', 'route' => 'panel.notifications.index', 'icon' => 'bell', 'match' => 'panel.notifications.*', 'tab' => true],
            ['label' => 'Güvenlik', 'route' => 'panel.security', 'icon' => 'shield-check', 'match' => 'panel.security*', 'tab' => false],
            ['label' => 'Ayarlar', 'route' => 'panel.settings', 'icon' => 'settings', 'match' => 'panel.settings*', 'tab' => false],
        ];

        $menu = array_map(fn (array $i): array => $i + ['href' => route($i['route']), 'active' => request()->routeIs($i['match'])], $items);

        $view->with([
            'panelMenu' => $menu,
            'panelUser' => $user,
            'unreadCount' => $user?->unreadNotifications()->count() ?? 0,
            'serverStatus' => $this->status->primary(),
        ]);
    }
}
