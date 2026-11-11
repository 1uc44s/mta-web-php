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

    /** Türlerin ACP etiketleri (docs/CMS.md §4.2). */
    public const LABELS = [
        'hero' => 'Karşılama', 'server_status' => 'Canlı sunucu durumu', 'feature_grid' => 'Özellik kartları',
        'world_gallery' => 'Dünyayı keşfet (galeri)', 'lifestyle_split' => 'Legal / illegal yaşam', 'onboarding_steps' => 'Yeni oyuncu adımları',
        'community' => 'Topluluk', 'news_latest' => 'Son haberler', 'events_upcoming' => 'Yaklaşan etkinlikler', 'faq_list' => 'SSS',
        'cta_banner' => 'Son çağrı', 'rich_text' => 'Serbest metin', 'stats_strip' => 'Doğrulanabilir sayılar',
    ];

    /**
     * Bölüm veri şeması (Laravel doğrulama kuralları) — docs/CMS.md §4.1. Sayılar ve listeler kaynaktan hesaplanır;
     * şema yalnızca editörün doldurabileceği alanları tanımlar. Şemada olmayan anahtar kaydedilmez.
     *
     * @return array<string, list<mixed>>
     */
    public static function schema(string $type): array
    {
        $head = [
            'eyebrow' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:80'],
            'intro' => ['nullable', 'string', 'max:300'],
        ];

        return match ($type) {
            'hero' => ['eyebrow' => $head['eyebrow'], 'title' => ['required', 'string', 'max:60'], 'title_accent' => ['nullable', 'string', 'max:60'], 'lead' => ['nullable', 'string', 'max:220'], 'secondary_label' => ['nullable', 'string', 'max:30']],
            'server_status', 'news_latest', 'events_upcoming', 'faq_list' => $head + ['count' => ['nullable', 'integer', 'in:3,6']],
            'feature_grid' => $head + [
                'variant' => ['required', 'in:grid-3,grid-4,bento'],
                'items' => ['required', 'array', 'min:1', 'max:9'],
                'items.*.icon' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/'],
                'items.*.title' => ['required', 'string', 'max:48'],
                'items.*.text' => ['required', 'string', 'max:160'],
                'items.*.system_slug' => ['nullable', 'string', 'exists:game_systems,slug'],
            ],
            'world_gallery' => $head + ['media_ids' => ['nullable', 'array', 'max:12'], 'media_ids.*' => ['integer', 'exists:media,id']],
            'lifestyle_split' => $head + [
                'columns' => ['required', 'array', 'size:2'],
                'columns.*.label' => ['required', 'string', 'max:30'],
                'columns.*.title' => ['required', 'string', 'max:80'],
                'columns.*.text' => ['required', 'string', 'max:300'],
                'columns.*.link_label' => ['nullable', 'string', 'max:40'],
                'columns.*.link_route' => ['nullable', 'string', 'regex:/^public\.[a-z.-]+$/'],
                'columns.*.link_params' => ['nullable', 'array'],
                'columns.*.link_params.*' => ['string', 'max:40'],
            ],
            'onboarding_steps' => $head + [
                'steps' => ['required', 'array', 'min:3', 'max:8'],
                'steps.*.title' => ['required', 'string', 'max:48'],
                'steps.*.text' => ['required', 'string', 'max:160'],
            ],
            'community' => $head,
            'cta_banner' => ['eyebrow' => $head['eyebrow'], 'title' => $head['title'], 'text' => ['nullable', 'string', 'max:220'], 'secondary_label' => ['nullable', 'string', 'max:30']],
            'rich_text' => ['eyebrow' => $head['eyebrow'], 'title' => ['nullable', 'string', 'max:80'], 'body' => ['required', 'string', 'max:20000']],
            'stats_strip' => ['title' => ['nullable', 'string', 'max:80'], 'metrics' => ['nullable', 'array'], 'metrics.*' => ['in:registered_users,factions_count,systems_count']],
            default => [],
        };
    }

    /** @return array<string, mixed> Yeni bölüm için varsayılan veri. */
    public static function defaults(string $type): array
    {
        return match ($type) {
            'hero' => ['title' => 'Şehrin ritmi,', 'title_accent' => 'senin hikâyen.'],
            'feature_grid' => ['title' => 'Şehirde neler var', 'variant' => 'grid-3', 'items' => [['icon' => 'star', 'title' => 'Başlık', 'text' => 'Kısa açıklama']]],
            'lifestyle_split' => ['title' => 'Hangi tarafta duracaksın?', 'columns' => [['label' => 'Yasal', 'title' => 'Başlık', 'text' => 'Metin'], ['label' => 'Yeraltı', 'title' => 'Başlık', 'text' => 'Metin']]],
            'onboarding_steps' => ['title' => 'İlk adımlar', 'steps' => [['title' => 'Adım 1', 'text' => 'Metin'], ['title' => 'Adım 2', 'text' => 'Metin'], ['title' => 'Adım 3', 'text' => 'Metin']]],
            'rich_text' => ['body' => 'Metin'],
            'stats_strip' => ['title' => 'Sayılarla şehir', 'metrics' => ['registered_users', 'factions_count', 'systems_count']],
            'news_latest', 'events_upcoming' => ['title' => self::LABELS[$type], 'count' => 3],
            default => ['title' => self::LABELS[$type] ?? 'Bölüm'],
        };
    }

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
