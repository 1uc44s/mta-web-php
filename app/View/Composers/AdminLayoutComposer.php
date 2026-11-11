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
            ['label' => 'game.admin.accounts', 'route' => 'admin.game-accounts.index', 'icon' => 'gamepad-2', 'can' => 'game_accounts.read', 'match' => 'admin.game-accounts.*'],
            ['label' => 'game.admin.characters', 'route' => 'admin.characters.index', 'icon' => 'contact', 'can' => 'characters.read', 'match' => 'admin.characters.*'],
        ]],
        ['label' => 'support.title', 'items' => [
            ['label' => 'support.admin.queue', 'route' => 'admin.support.index', 'icon' => 'life-buoy', 'can' => 'support.read', 'match' => 'admin.support.*'],
        ]],
        ['label' => 'content_admin.nav.content', 'items' => [
            ['label' => 'Sayfalar', 'route' => 'admin.content.pages.index', 'icon' => 'file-text', 'can' => 'content.read', 'match' => 'admin.content.pages.*'],
            ['label' => 'Haberler', 'route' => 'admin.content.news.index', 'icon' => 'newspaper', 'can' => 'content.read', 'match' => 'admin.content.news*'],
            ['label' => 'Etkinlikler', 'route' => 'admin.content.events.index', 'icon' => 'calendar', 'can' => 'content.read', 'match' => 'admin.content.events.*'],
            ['label' => 'SSS', 'route' => 'admin.content.faqs.index', 'icon' => 'circle-help', 'can' => 'content.read', 'match' => 'admin.content.faqs.*'],
            ['label' => 'Navigasyon', 'route' => 'admin.content.navigation.index', 'icon' => 'menu', 'can' => 'content.read', 'match' => 'admin.content.navigation.*'],
            ['label' => 'Sosyal bağlantılar', 'route' => 'admin.content.social-links.index', 'icon' => 'share-2', 'can' => 'content.read', 'match' => 'admin.content.social-links.*'],
            ['label' => 'content_admin.nav.media', 'route' => 'admin.media.index', 'icon' => 'image', 'can' => 'content.read', 'match' => 'admin.media.*'],
        ]],
        ['label' => 'content_admin.nav.game', 'items' => [
            ['label' => 'Oyun sistemleri', 'route' => 'admin.content.systems.index', 'icon' => 'cpu', 'can' => 'content.read', 'match' => 'admin.content.system*'],
            ['label' => 'Meslekler', 'route' => 'admin.content.jobs.index', 'icon' => 'briefcase', 'can' => 'content.read', 'match' => 'admin.content.jobs.*'],
            ['label' => 'Fraksiyonlar', 'route' => 'admin.content.factions.index', 'icon' => 'shield', 'can' => 'content.read', 'match' => 'admin.content.factions.*'],
            ['label' => 'Kurallar', 'route' => 'admin.content.rules.index', 'icon' => 'scale', 'can' => 'content.read', 'match' => 'admin.content.rule*'],
            ['label' => 'Akademi dersleri', 'route' => 'admin.content.academy-courses.index', 'icon' => 'graduation-cap', 'can' => 'academy.manage', 'match' => 'admin.content.academy-courses.*'],
            ['label' => 'Akademi konuları', 'route' => 'admin.content.academy-topics.index', 'icon' => 'book-open', 'can' => 'academy.manage', 'match' => 'admin.content.academy-topics.*'],
        ]],
        ['label' => 'content_admin.nav.communication', 'items' => [
            ['label' => 'Duyurular', 'route' => 'admin.announcements.index', 'icon' => 'megaphone', 'can' => 'announcements.manage', 'match' => 'admin.announcements.*'],
            ['label' => 'content_admin.nav.templates', 'route' => 'admin.templates.index', 'icon' => 'mail', 'can' => 'templates.read', 'match' => 'admin.templates.*'],
            ['label' => 'content_admin.nav.contact', 'route' => 'admin.contact.index', 'icon' => 'inbox', 'can' => 'contact.read', 'match' => 'admin.contact.*'],
        ]],
        ['label' => 'admin.nav.team', 'items' => [
            ['label' => 'admin.nav.staff', 'route' => 'admin.staff.index', 'icon' => 'shield-user', 'can' => 'staff.read', 'match' => 'admin.staff.*'],
            ['label' => 'admin.nav.branches', 'route' => 'admin.branches.index', 'icon' => 'git-branch', 'can' => 'staff.read', 'match' => 'admin.branches.*'],
            ['label' => 'admin.nav.roles', 'route' => 'admin.roles.index', 'icon' => 'key-round', 'can' => 'roles.read', 'match' => 'admin.roles.*'],
        ]],
        ['label' => 'admin.nav.system', 'items' => [
            ['label' => 'admin.nav.settings', 'route' => 'admin.settings.index', 'icon' => 'sliders-horizontal', 'can' => 'settings.read', 'match' => 'admin.settings.*'],
            ['label' => 'game.admin.integrations', 'route' => 'admin.integrations.mta', 'icon' => 'plug', 'can' => 'integrations.manage', 'match' => 'admin.integrations.*'],
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
