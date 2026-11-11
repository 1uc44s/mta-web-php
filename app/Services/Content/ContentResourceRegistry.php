<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\AcademyCourse;
use App\Models\AcademyTopic;
use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\Faction;
use App\Models\Faq;
use App\Models\GameJob;
use App\Models\GameSystem;
use App\Models\GameSystemCategory;
use App\Models\NavigationItem;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Rule;
use App\Models\RuleCategory;
use App\Models\SocialLink;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * ACP CMS kaynak deseni — docs/ROUTES.md §6.8 (AdminResource), CMS §3. Her kaynak: model, izin ailesi, alanlar, liste kolonları.
 * Form ve doğrulama bu tanımdan üretilir; özel ekranlar (bölüm düzenleyici, revizyonlar) ayrı controller'lardadır.
 *
 * Alan tipleri: text · textarea · markdown · select · bool · int · datetime · url · media · belongs · tags · rules · channels
 *
 * @phpstan-type ContentField array{type: string, label: string, rules?: list<mixed>, options?: array<string, string>, model?: class-string<Model>, display?: string, help?: string, wide?: bool, default?: scalar}
 * @phpstan-type ContentDef array{model: class-string<Model>, label: string, singular: string, family: string, title: string, publishable: bool, sortable: bool, slug_from: string|null, search: list<string>, columns: list<string>, fields: array<string, ContentField>, path?: string, name?: string, public?: string, revisions?: string, system_guard?: bool}
 */
final class ContentResourceRegistry
{
    private const MD = ['nullable', 'string', 'max:60000'];

    private const MD_REQUIRED = ['required', 'string', 'max:60000'];

    /** @return array<string, ContentDef> */
    public static function all(): array
    {
        $severity = ['' => '—', 'warning' => 'Uyarı', 'jail' => 'Hapis', 'kick' => 'Kick', 'ban' => 'Ban'];

        return [
            'pages' => [
                'model' => Page::class, 'label' => 'Sayfalar', 'singular' => 'Sayfa', 'family' => 'content', 'title' => 'title',
                'publishable' => true, 'sortable' => false, 'slug_from' => 'title', 'search' => ['title', 'slug'],
                'columns' => ['title', 'slug', 'template'], 'revisions' => 'page', 'system_guard' => true,
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Başlık', 'rules' => ['required', 'string', 'max:150']],
                    'template' => ['type' => 'select', 'label' => 'Şablon', 'options' => ['standard' => 'Standart metin', 'landing' => 'Bölümlü (landing)', 'home' => 'Ana sayfa (bölümlü)', 'legal' => 'Yasal metin'], 'rules' => ['required']],
                    'body' => ['type' => 'markdown', 'label' => 'Gövde (Markdown)', 'rules' => self::MD, 'wide' => true],
                    'version_label' => ['type' => 'text', 'label' => 'Sürüm etiketi (yasal)', 'rules' => ['nullable', 'string', 'max:32']],
                    'effective_at' => ['type' => 'datetime', 'label' => 'Yürürlük tarihi (yasal)', 'rules' => ['nullable', 'date']],
                    'og_media_id' => ['type' => 'media', 'label' => 'Paylaşım görseli'],
                ],
            ],
            'news' => [
                'model' => NewsPost::class, 'label' => 'Haberler', 'singular' => 'Haber', 'family' => 'content', 'title' => 'title',
                'publishable' => true, 'sortable' => false, 'slug_from' => 'title', 'search' => ['title', 'slug'],
                'columns' => ['title', 'news_category_id', 'is_pinned'], 'public' => 'public.news.show',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Başlık', 'rules' => ['required', 'string', 'max:150']],
                    'news_category_id' => ['type' => 'belongs', 'label' => 'Kategori', 'model' => NewsCategory::class, 'display' => 'name', 'rules' => ['required']],
                    'excerpt' => ['type' => 'textarea', 'label' => 'Özet', 'rules' => ['required', 'string', 'max:300']],
                    'body' => ['type' => 'markdown', 'label' => 'Gövde (Markdown)', 'rules' => ['required', 'string', 'max:60000'], 'wide' => true],
                    'cover_media_id' => ['type' => 'media', 'label' => 'Kapak görseli'],
                    'author_label' => ['type' => 'text', 'label' => 'Yazar etiketi', 'rules' => ['nullable', 'string', 'max:64'], 'default' => 'Yönetim Ekibi', 'help' => 'Boşsa "Yönetim Ekibi" görünür.'],
                    'is_pinned' => ['type' => 'bool', 'label' => 'Üste sabitle'],
                    'tags' => ['type' => 'tags', 'label' => 'Etiketler', 'help' => 'Virgülle ayır (ör. güncelleme, etkinlik).'],
                ],
            ],
            'news-categories' => [
                'model' => NewsCategory::class, 'label' => 'Haber kategorileri', 'singular' => 'Kategori', 'family' => 'content', 'title' => 'name',
                'publishable' => false, 'sortable' => true, 'slug_from' => 'name', 'search' => ['name'], 'columns' => ['name', 'slug'],
                'fields' => ['name' => ['type' => 'text', 'label' => 'Ad', 'rules' => ['required', 'string', 'max:80']]],
            ],
            'tags' => [
                'model' => Tag::class, 'label' => 'Etiketler', 'singular' => 'Etiket', 'family' => 'content', 'title' => 'name',
                'publishable' => false, 'sortable' => false, 'slug_from' => 'name', 'search' => ['name'], 'columns' => ['name', 'slug'],
                'fields' => ['name' => ['type' => 'text', 'label' => 'Ad', 'rules' => ['required', 'string', 'max:50']]],
            ],
            'systems' => [
                'model' => GameSystem::class, 'label' => 'Oyun sistemleri', 'singular' => 'Oyun sistemi', 'family' => 'content', 'title' => 'name',
                'publishable' => true, 'sortable' => true, 'slug_from' => 'name', 'search' => ['name', 'slug'],
                'columns' => ['name', 'game_system_category_id', 'is_live_in_game'], 'public' => 'public.systems.show',
                'fields' => [
                    'name' => ['type' => 'text', 'label' => 'Ad', 'rules' => ['required', 'string', 'max:120']],
                    'game_system_category_id' => ['type' => 'belongs', 'label' => 'Kategori', 'model' => GameSystemCategory::class, 'display' => 'name', 'rules' => ['required']],
                    'summary' => ['type' => 'textarea', 'label' => 'Kısa açıklama', 'rules' => ['required', 'string', 'max:300']],
                    'how_it_works' => ['type' => 'markdown', 'label' => 'Nasıl çalışır?', 'rules' => self::MD_REQUIRED, 'wide' => true],
                    'how_to_access' => ['type' => 'markdown', 'label' => 'Nasıl erişilir?', 'rules' => ['nullable', 'string', 'max:10000']],
                    'requirements' => ['type' => 'markdown', 'label' => 'Gereksinimler', 'rules' => ['nullable', 'string', 'max:10000']],
                    'media_id' => ['type' => 'media', 'label' => 'Görsel'],
                    'video_url' => ['type' => 'url', 'label' => 'YouTube videosu', 'rules' => ['nullable', 'url:https', 'max:255', 'regex:#^https://(www\.)?(youtube\.com|youtu\.be|youtube-nocookie\.com)/#'], 'help' => 'Yalnızca YouTube bağlantısı.'],
                    'is_live_in_game' => ['type' => 'bool', 'label' => 'Oyunda aktif'],
                ],
            ],
            'system-categories' => [
                'model' => GameSystemCategory::class, 'label' => 'Sistem kategorileri', 'singular' => 'Kategori', 'family' => 'content', 'title' => 'name',
                'publishable' => false, 'sortable' => true, 'slug_from' => 'name', 'search' => ['name'], 'columns' => ['name', 'icon'],
                'fields' => [
                    'name' => ['type' => 'text', 'label' => 'Ad', 'rules' => ['required', 'string', 'max:80']],
                    'icon' => ['type' => 'text', 'label' => 'Lucide ikon adı', 'rules' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/']],
                ],
            ],
            'jobs' => [
                'model' => GameJob::class, 'label' => 'Meslekler', 'singular' => 'Meslek', 'family' => 'content', 'title' => 'name',
                'publishable' => true, 'sortable' => true, 'slug_from' => 'name', 'search' => ['name', 'slug'], 'columns' => ['name', 'class', 'is_active'], 'public' => 'public.jobs.show',
                'fields' => [
                    'name' => ['type' => 'text', 'label' => 'Ad', 'rules' => ['required', 'string', 'max:120']],
                    'class' => ['type' => 'select', 'label' => 'Sınıf', 'options' => ['legal' => 'Legal', 'special' => 'Özel', 'event' => 'Etkinlik'], 'rules' => ['required']],
                    'summary' => ['type' => 'textarea', 'label' => 'Kısa açıklama', 'rules' => ['required', 'string', 'max:300']],
                    'description' => ['type' => 'markdown', 'label' => 'Açıklama', 'rules' => self::MD_REQUIRED, 'wide' => true],
                    'duties' => ['type' => 'markdown', 'label' => 'Görevler', 'rules' => ['nullable', 'string', 'max:10000']],
                    'progression' => ['type' => 'markdown', 'label' => 'İlerleme', 'rules' => ['nullable', 'string', 'max:10000']],
                    'earnings_info' => ['type' => 'textarea', 'label' => 'Kazanç bilgisi', 'rules' => ['nullable', 'string', 'max:2000']],
                    'access_requirement' => ['type' => 'text', 'label' => 'Erişim şartı', 'rules' => ['nullable', 'string', 'max:150']],
                    'media_id' => ['type' => 'media', 'label' => 'Görsel'],
                    'is_active' => ['type' => 'bool', 'label' => 'Oyunda aktif'],
                ],
            ],
            'factions' => [
                'model' => Faction::class, 'label' => 'Fraksiyonlar', 'singular' => 'Fraksiyon', 'family' => 'content', 'title' => 'name',
                'publishable' => true, 'sortable' => true, 'slug_from' => 'name', 'search' => ['name', 'slug'], 'columns' => ['name', 'type', 'applications_open'], 'public' => 'public.factions.show',
                'fields' => [
                    'name' => ['type' => 'text', 'label' => 'Ad', 'rules' => ['required', 'string', 'max:120']],
                    'type' => ['type' => 'select', 'label' => 'Tür', 'options' => ['legal' => 'Legal', 'illegal' => 'İllegal', 'government' => 'Devlet', 'private' => 'Özel'], 'rules' => ['required']],
                    'summary' => ['type' => 'textarea', 'label' => 'Kısa açıklama', 'rules' => ['required', 'string', 'max:300']],
                    'description' => ['type' => 'markdown', 'label' => 'Açıklama', 'rules' => self::MD_REQUIRED, 'wide' => true],
                    'min_requirements' => ['type' => 'markdown', 'label' => 'Asgari gereksinimler', 'rules' => ['nullable', 'string', 'max:10000']],
                    'logo_media_id' => ['type' => 'media', 'label' => 'Logo'],
                    'cover_media_id' => ['type' => 'media', 'label' => 'Kapak görseli'],
                    'rule_category_id' => ['type' => 'belongs', 'label' => 'Kural kategorisi', 'model' => RuleCategory::class, 'display' => 'name'],
                    'accent_token' => ['type' => 'select', 'label' => 'Vurgu rengi', 'options' => ['' => 'Varsayılan', 'accent' => 'Turkuaz', 'amber' => 'Amber', 'info' => 'Mavi', 'danger' => 'Kırmızı'], 'rules' => ['nullable']],
                    'applications_open' => ['type' => 'bool', 'label' => 'Başvurular açık'],
                ],
            ],
            'rule-categories' => [
                'model' => RuleCategory::class, 'label' => 'Kural kategorileri', 'singular' => 'Kural kategorisi', 'family' => 'content', 'title' => 'name',
                'publishable' => true, 'sortable' => true, 'slug_from' => 'name', 'search' => ['name'], 'columns' => ['name', 'prefix'],
                'fields' => [
                    'name' => ['type' => 'text', 'label' => 'Ad', 'rules' => ['required', 'string', 'max:100']],
                    'prefix' => ['type' => 'text', 'label' => 'Kod öneki (ör. RP)', 'rules' => ['required', 'string', 'max:8', 'regex:/^[A-Z]{1,8}$/']],
                    'description' => ['type' => 'textarea', 'label' => 'Açıklama', 'rules' => ['nullable', 'string', 'max:255']],
                ],
            ],
            'rules' => [
                'model' => Rule::class, 'label' => 'Kurallar', 'singular' => 'Kural', 'family' => 'content', 'title' => 'title',
                'publishable' => true, 'sortable' => true, 'slug_from' => null, 'search' => ['code', 'title'], 'columns' => ['code', 'title', 'rule_category_id'], 'revisions' => 'rule',
                'fields' => [
                    'rule_category_id' => ['type' => 'belongs', 'label' => 'Kategori', 'model' => RuleCategory::class, 'display' => 'name', 'rules' => ['required']],
                    'code' => ['type' => 'text', 'label' => 'Kod (ör. RP-4.2)', 'rules' => ['required', 'string', 'max:16', 'regex:/^[A-Z]{1,8}-\d{1,3}(\.\d{1,3})?$/']],
                    'title' => ['type' => 'text', 'label' => 'Başlık', 'rules' => ['required', 'string', 'max:150']],
                    'body' => ['type' => 'markdown', 'label' => 'Kural metni', 'rules' => ['required', 'string', 'max:20000'], 'wide' => true],
                    'severity_hint' => ['type' => 'select', 'label' => 'Ciddiyet ipucu', 'options' => $severity, 'rules' => ['nullable']],
                ],
            ],
            'faqs' => [
                'model' => Faq::class, 'label' => 'SSS', 'singular' => 'Soru', 'family' => 'content', 'title' => 'question',
                'publishable' => true, 'sortable' => true, 'slug_from' => null, 'search' => ['question'], 'columns' => ['question', 'group', 'show_on_home'],
                'fields' => [
                    'group' => ['type' => 'text', 'label' => 'Grup', 'rules' => ['required', 'string', 'max:50']],
                    'question' => ['type' => 'text', 'label' => 'Soru', 'rules' => ['required', 'string', 'max:200']],
                    'answer' => ['type' => 'markdown', 'label' => 'Cevap', 'rules' => ['required', 'string', 'max:5000'], 'wide' => true],
                    'show_on_home' => ['type' => 'bool', 'label' => 'Ana sayfada göster'],
                ],
            ],
            'events' => [
                'model' => CommunityEvent::class, 'label' => 'Etkinlikler', 'singular' => 'Etkinlik', 'family' => 'content', 'title' => 'title',
                'publishable' => true, 'sortable' => false, 'slug_from' => 'title', 'search' => ['title'], 'columns' => ['title', 'starts_at'], 'public' => 'public.events.show',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Başlık', 'rules' => ['required', 'string', 'max:150']],
                    'summary' => ['type' => 'textarea', 'label' => 'Özet', 'rules' => ['required', 'string', 'max:300']],
                    'description' => ['type' => 'markdown', 'label' => 'Açıklama', 'rules' => self::MD_REQUIRED, 'wide' => true],
                    'starts_at' => ['type' => 'datetime', 'label' => 'Başlangıç', 'rules' => ['required', 'date']],
                    'ends_at' => ['type' => 'datetime', 'label' => 'Bitiş', 'rules' => ['nullable', 'date', 'after:starts_at']],
                    'location_label' => ['type' => 'text', 'label' => 'Konum etiketi', 'rules' => ['nullable', 'string', 'max:100']],
                    'cover_media_id' => ['type' => 'media', 'label' => 'Kapak görseli'],
                ],
            ],
            'navigation' => [
                'model' => NavigationItem::class, 'label' => 'Navigasyon', 'singular' => 'Menü öğesi', 'family' => 'content', 'title' => 'label',
                'publishable' => false, 'sortable' => true, 'slug_from' => null, 'search' => ['label'], 'columns' => ['label', 'location', 'is_visible'],
                'fields' => [
                    'location' => ['type' => 'select', 'label' => 'Konum', 'options' => ['header' => 'Üst menü', 'footer_quick' => 'Alt: hızlı bağlantılar', 'footer_community' => 'Alt: topluluk', 'footer_legal' => 'Alt: yasal'], 'rules' => ['required']],
                    'label' => ['type' => 'text', 'label' => 'Etiket', 'rules' => ['required', 'string', 'max:50']],
                    'route_name' => ['type' => 'text', 'label' => 'Rota adı (ör. public.rules)', 'rules' => ['nullable', 'required_without:url', 'string', 'max:100', 'regex:/^public\.[a-z.-]+$/']],
                    'url' => ['type' => 'url', 'label' => 'veya URL (https)', 'rules' => ['nullable', 'required_without:route_name', 'url:https', 'max:500']],
                    'visibility' => ['type' => 'select', 'label' => 'Görünürlük', 'options' => ['all' => 'Herkes', 'guest' => 'Yalnızca misafir', 'auth' => 'Yalnızca giriş yapmış'], 'rules' => ['required']],
                    'opens_new_tab' => ['type' => 'bool', 'label' => 'Yeni sekmede aç'],
                    'is_visible' => ['type' => 'bool', 'label' => 'Görünür'],
                ],
            ],
            'social-links' => [
                'model' => SocialLink::class, 'label' => 'Sosyal bağlantılar', 'singular' => 'Bağlantı', 'family' => 'content', 'title' => 'label',
                'publishable' => false, 'sortable' => true, 'slug_from' => null, 'search' => ['label'], 'columns' => ['label', 'platform', 'is_visible'],
                'fields' => [
                    'platform' => ['type' => 'select', 'label' => 'Platform', 'options' => ['discord' => 'Discord', 'youtube' => 'YouTube', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'x' => 'X', 'twitch' => 'Twitch'], 'rules' => ['required']],
                    'label' => ['type' => 'text', 'label' => 'Etiket', 'rules' => ['required', 'string', 'max:50']],
                    'url' => ['type' => 'url', 'label' => 'URL (https)', 'rules' => ['required', 'url:https', 'max:500']],
                    'is_visible' => ['type' => 'bool', 'label' => 'Görünür'],
                ],
            ],
            'academy-courses' => [
                'model' => AcademyCourse::class, 'label' => 'Akademi dersleri', 'singular' => 'Ders', 'family' => 'academy', 'title' => 'title',
                'publishable' => true, 'sortable' => true, 'slug_from' => 'title', 'search' => ['title'], 'columns' => ['title', 'level', 'is_required'], 'public' => 'public.academy.course',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Başlık', 'rules' => ['required', 'string', 'max:150']],
                    'summary' => ['type' => 'textarea', 'label' => 'Özet', 'rules' => ['required', 'string', 'max:300']],
                    'level' => ['type' => 'select', 'label' => 'Seviye', 'options' => ['beginner' => 'Başlangıç', 'intermediate' => 'Orta', 'advanced' => 'İleri'], 'rules' => ['required']],
                    'estimated_minutes' => ['type' => 'int', 'label' => 'Tahmini süre (dk)', 'rules' => ['nullable', 'integer', 'min:1', 'max:600'], 'default' => 5],
                    'cover_media_id' => ['type' => 'media', 'label' => 'Kapak görseli'],
                    'is_required' => ['type' => 'bool', 'label' => 'Zorunlu ders'],
                    'is_onboarding' => ['type' => 'bool', 'label' => 'Başlangıç (/start) dersi', 'help' => 'Sistemde tek ders olabilir; işaretlenirse diğerlerinden kaldırılır.'],
                ],
            ],
            'academy-topics' => [
                'model' => AcademyTopic::class, 'label' => 'Akademi konuları', 'singular' => 'Konu', 'family' => 'academy', 'title' => 'title',
                'publishable' => true, 'sortable' => true, 'slug_from' => 'title', 'search' => ['title'], 'columns' => ['title', 'academy_course_id', 'step_label'],
                'fields' => [
                    'academy_course_id' => ['type' => 'belongs', 'label' => 'Ders', 'model' => AcademyCourse::class, 'display' => 'title', 'rules' => ['required']],
                    'title' => ['type' => 'text', 'label' => 'Başlık', 'rules' => ['required', 'string', 'max:150']],
                    'body' => ['type' => 'markdown', 'label' => 'İçerik', 'rules' => ['required', 'string', 'max:60000'], 'wide' => true],
                    'step_label' => ['type' => 'text', 'label' => '/start adım başlığı', 'rules' => ['nullable', 'string', 'max:48']],
                    'estimated_minutes' => ['type' => 'int', 'label' => 'Tahmini süre (dk)', 'rules' => ['nullable', 'integer', 'min:1', 'max:240'], 'default' => 3],
                    'rules' => ['type' => 'rules', 'label' => 'İlgili kurallar', 'help' => 'Kural kodları, virgülle (ör. RP-2.1, RP-2.3).'],
                ],
            ],
            'announcements' => [
                'model' => Announcement::class, 'label' => 'Duyurular', 'singular' => 'Duyuru', 'family' => 'announcements', 'title' => 'title',
                'publishable' => true, 'sortable' => false, 'slug_from' => null, 'search' => ['title'], 'columns' => ['title', 'priority', 'starts_at'],
                'path' => 'announcements', 'name' => 'announcements',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Başlık', 'rules' => ['required', 'string', 'max:150']],
                    'body' => ['type' => 'markdown', 'label' => 'Metin', 'rules' => ['required', 'string', 'max:5000'], 'wide' => true],
                    'priority' => ['type' => 'select', 'label' => 'Önem', 'options' => ['info' => 'Bilgi', 'important' => 'Önemli', 'critical' => 'Kritik'], 'rules' => ['required']],
                    'audience' => ['type' => 'select', 'label' => 'Hedef kitle', 'options' => ['all' => 'Herkes (site bandı dahil)', 'players' => 'Giriş yapmış oyuncular'], 'rules' => ['required']],
                    'channels' => ['type' => 'channels', 'label' => 'Kanallar', 'options' => ['public_banner' => 'Site bandı', 'panel' => 'Oyuncu paneli', 'email' => 'E-posta (yayında bir kez)']],
                    'starts_at' => ['type' => 'datetime', 'label' => 'Başlangıç', 'rules' => ['required', 'date']],
                    'ends_at' => ['type' => 'datetime', 'label' => 'Bitiş', 'rules' => ['nullable', 'date', 'after:starts_at']],
                ],
            ],
        ];
    }

    /** @return ContentDef */
    public static function get(string $key): array
    {
        return self::all()[$key] ?? throw new InvalidArgumentException("Bilinmeyen içerik kaynağı: {$key}");
    }

    /** İşlem → izin (docs/ROUTES.md §6.8 varsayılanları; academy-* ve duyurular için istisnalar). */
    public static function permission(string $key, string $ability): string
    {
        return match (self::get($key)['family']) {
            'academy' => 'academy.manage',
            'announcements' => $ability === 'publish' ? 'announcements.publish' : 'announcements.manage',
            default => match ($ability) {
                'read' => 'content.read',
                'create' => 'content.create',
                'update' => 'content.update',
                'publish' => 'content.publish',
                'delete' => 'content.delete',
                default => throw new InvalidArgumentException($ability),
            },
        };
    }

    /** Rota adı öneki: `admin.content.{key}` veya özel ad (duyurular: `admin.announcements`). */
    public static function routeName(string $key): string
    {
        $resource = self::get($key);

        return 'admin.'.($resource['name'] ?? 'content.'.$key);
    }
}
