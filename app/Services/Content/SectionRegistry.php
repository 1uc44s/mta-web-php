<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\CommunityEvent;
use App\Models\Faction;
use App\Models\Faq;
use App\Models\GameSystem;
use App\Models\Media;
use App\Models\NewsPost;
use App\Models\PageSection;
use App\Models\SocialLink;
use App\Models\User;
use App\Services\Bridge\ServerStatusResolver;
use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Sayfa bölümü sistem şablonları (docs/CMS.md §4). Her tür = Blade görünümü + veri yükleyici.
 * Editör yalnızca `data` alanını doldurur; sayılar ve listeler kaynaktan hesaplanır (sahte sayı yok).
 * Şema doğrulaması ACP bölüm düzenleyicisi ile Faz 5'te eklenir.
 */
final class SectionRegistry
{
    /** @var list<string> */
    public const TYPES = [
        'hero', 'server_status', 'feature_grid', 'world_gallery', 'lifestyle_split', 'onboarding_steps',
        'community', 'news_latest', 'events_upcoming', 'faq_list', 'cta_banner', 'rich_text', 'stats_strip',
    ];

    public function __construct(
        private readonly ServerStatusResolver $status,
        private readonly MarkdownRenderer $markdown,
        private readonly SettingsRepository $settings,
    ) {}

    public function supports(string $type): bool
    {
        return in_array($type, self::TYPES, true) && view()->exists('sections.'.str_replace('_', '-', $type));
    }

    public function view(string $type): string
    {
        return 'sections.'.str_replace('_', '-', $type);
    }

    /**
     * Görünüme verilecek veri. null dönerse bölüm render edilmez (ör. gösterilecek kayıt yok).
     *
     * @return array<string, mixed>|null
     */
    public function viewData(PageSection $section): ?array
    {
        /** @var array<string, mixed> $data */
        $data = $section->data ?? [];

        return match ($section->type) {
            'server_status' => ['status' => $this->status->primary()],
            'hero' => $data + ['status' => $this->status->primary()],
            'feature_grid' => $this->featureGrid($data),
            'news_latest' => $this->newsLatest($data),
            'events_upcoming' => $this->eventsUpcoming($data),
            'faq_list' => $this->faqList($data),
            'stats_strip' => $this->statsStrip($data),
            'rich_text' => $data + ['html' => $this->markdown->render($data['body'] ?? '')],
            'world_gallery' => $this->worldGallery($data),
            'community' => $data + [
                'discord' => $this->settings->get('social.discord_invite'),
                'socials' => SocialLink::query()->visible()->get(),
            ],
            default => $data,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function worldGallery(array $data): ?array
    {
        $ids = array_map('intval', (array) ($data['media_ids'] ?? []));
        $media = $ids === [] ? collect() : Media::query()->whereIn('id', $ids)->get();

        return $media->isEmpty() ? null : $data + ['media' => $media];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function featureGrid(array $data): ?array
    {
        $items = collect(is_array($data['items'] ?? null) ? $data['items'] : []);
        $slugs = $items->pluck('system_slug')->filter()->values()->all();
        $live = $slugs === [] ? [] : GameSystem::query()->visible()->whereIn('slug', $slugs)->pluck('slug')->all();

        // Referans verilen sistem yayında değilse kart gösterilmez (docs/CMS.md §4.1).
        $data['items'] = $items
            ->filter(fn (array $item): bool => empty($item['system_slug']) || in_array($item['system_slug'], $live, true))
            ->values()
            ->all();

        return $data['items'] === [] ? null : $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function newsLatest(array $data): ?array
    {
        $posts = NewsPost::query()->published()->with(['category', 'cover'])
            ->orderByDesc('is_pinned')->orderByDesc('published_at')
            ->limit((int) ($data['count'] ?? 3))->get();

        return $posts->isEmpty() ? null : $data + ['posts' => $posts];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function eventsUpcoming(array $data): ?array
    {
        $events = CommunityEvent::query()->upcoming()->limit((int) ($data['count'] ?? 3))->get();

        return $events->isEmpty() ? null : $data + ['events' => $events];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function faqList(array $data): ?array
    {
        $faqs = Faq::query()->published()->where('show_on_home', true)->ordered()->get();

        return $faqs->isEmpty() ? null : $data + [
            'faqs' => $faqs,
            'answers' => $faqs->mapWithKeys(fn (Faq $faq): array => [$faq->id => $this->markdown->render($faq->answer)]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function statsStrip(array $data): ?array
    {
        $values = Cache::remember('stats-strip:v1', 300, fn (): array => [
            'registered_users' => User::query()->count(),
            'factions_count' => Faction::query()->published()->count(),
            'systems_count' => GameSystem::query()->visible()->count(),
        ]);

        $metrics = collect(is_array($data['metrics'] ?? null) ? $data['metrics'] : array_keys($values))
            ->filter(fn (string $key): bool => ($values[$key] ?? 0) > 0)
            ->map(fn (string $key): array => ['key' => $key, 'value' => $values[$key]])
            ->values()
            ->all();

        // array_merge: $data içindeki anahtar listesi hesaplanan değerlerle değiştirilir (+ operatörü ezmez).
        return $metrics === [] ? null : array_merge($data, ['metrics' => $metrics]);
    }
}
