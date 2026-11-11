<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\NavigationItem;
use App\Models\SocialLink;
use App\Services\Bridge\ServerStatusResolver;
use App\Services\Content\AnnouncementFeed;
use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Public layout verisi: navigasyon, sosyal bağlantılar, public ayarlar ve sunucu durumu (docs/SRS.md FR-PUB-001/002).
 * Cache'e yalnızca düz diziler yazılır: Laravel 13 cache'i güvenlik gereği nesne unserialize etmez.
 */
final class PublicLayoutComposer
{
    public const CACHE_KEY = 'nav:v2';

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly ServerStatusResolver $status,
        private readonly AnnouncementFeed $announcements,
    ) {}

    public function compose(View $view): void
    {
        $authenticated = auth()->check();

        $data = Cache::rememberForever(self::CACHE_KEY, fn (): array => [
            ...collect(['header', 'footer_quick', 'footer_community', 'footer_legal'])->mapWithKeys(
                fn (string $location): array => [$location => NavigationItem::query()->at($location)->get()
                    ->map(fn (NavigationItem $item): array => [
                        'label' => $item->label,
                        'href' => $item->href(),
                        'route' => $item->route_name,
                        'new_tab' => $item->opens_new_tab,
                        'visibility' => $item->visibility,
                    ])->all()],
            )->all(),
            'social' => SocialLink::query()->visible()->get(['label', 'url', 'platform'])->toArray(),
        ]);

        // Görünürlük ve aktiflik isteğe bağlıdır; cache dışında hesaplanır.
        $navigation = collect($data)->map(fn (array $items, string $key): array => $key === 'social' ? $items : collect($items)
            ->filter(fn (array $item): bool => match ($item['visibility']) {
                'guest' => ! $authenticated,
                'auth' => $authenticated,
                default => true,
            })
            ->map(fn (array $item): array => $item + [
                'active' => $item['route'] !== null && request()->routeIs($item['route'], $item['route'].'.*'),
            ])
            ->values()
            ->all())->all();

        $view->with([
            'navigation' => $navigation,
            'site' => $this->settings->public(),
            'serverStatus' => $this->status->primary(),
            'bannerAnnouncements' => $this->announcements->banner(),
        ]);
    }
}
