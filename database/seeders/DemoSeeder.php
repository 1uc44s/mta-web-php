<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CommunityEvent;
use App\Models\Faction;
use App\Models\GameJob;
use App\Models\GameSystem;
use App\Models\GameSystemCategory;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Yalnızca yerel geliştirme için örnek içerik (docs/DATABASE.md §12). Production'da çalışmayı reddeder.
 * Tüm metinler "Örnek içerik" olarak işaretlidir; gerçek sunucu içeriğiyle ACP'den değiştirilmelidir.
 */
final class DemoSeeder extends Seeder
{
    private const NOTE = "\n\n> _Örnek içerik — yönetim panelinden değiştirilecek._";

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder production ortamında çalıştırılamaz.');
        }

        $published = ['status' => 'published', 'published_at' => now()->subHour()];

        $categories = [
            'iletisim' => ['İletişim', 'mic', [['sesli-iletisim', 'Sesli iletişim', 'Yakınlık tabanlı ses, telsiz ve telefon.']]],
            'ekonomi' => ['Ekonomi', 'wallet', [['ekonomi', 'Ekonomi ve bankacılık', 'Maaş, banka hesabı, vergiler ve ticaret.']]],
            'yasam' => ['Yaşam', 'house', [
                ['arac-ve-garaj', 'Araç ve garaj', 'Ruhsat, sigorta, park ve modifiye.'],
                ['ev-ve-mulk', 'Ev ve mülk', 'Satın alma, kiralama ve iç düzenleme.'],
            ]],
            'organizasyon' => ['Organizasyon', 'shield', [
                ['fraksiyonlar', 'Fraksiyon sistemi', 'Rütbeler, telsiz kanalları ve görev akışları.'],
                ['karakter-gelisimi', 'Karakter gelişimi', 'Beceri, itibar ve karakter geçmişi.'],
            ]],
        ];

        $sort = 0;
        foreach ($categories as $slug => [$name, $icon, $systems]) {
            $category = GameSystemCategory::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'icon' => $icon, 'sort' => $sort++]);
            foreach ($systems as $i => [$systemSlug, $systemName, $summary]) {
                GameSystem::query()->updateOrCreate(['slug' => $systemSlug], [
                    'game_system_category_id' => $category->id, 'name' => $systemName, 'summary' => $summary,
                    'how_it_works' => "{$summary} Bu sistemin oyundaki işleyişi burada adım adım anlatılır.".self::NOTE,
                    'how_to_access' => 'Şehre giriş yaptıktan sonra ilgili NPC veya menü üzerinden erişilir.',
                    'is_live_in_game' => true, 'sort' => $i, 'last_reviewed_at' => now(), ...$published,
                ]);
            }
        }

        $jobs = [
            ['taksi-soforu', 'Taksi şoförü', 'legal', 'Şehrin her köşesine yolcu taşı, müdavimlerini edin.'],
            ['tamirci', 'Tamirci', 'legal', 'Araçları onar, modifiye siparişlerini karşıla.'],
            ['muhabir', 'Muhabir', 'special', 'Şehirdeki olayları takip et, haber yap. Başvuru gerektirir.'],
            ['etkinlik-gorevlisi', 'Etkinlik görevlisi', 'event', 'Topluluk etkinliklerinde organizasyon ekibine katıl.'],
        ];
        foreach ($jobs as $i => [$slug, $name, $class, $summary]) {
            GameJob::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name, 'class' => $class, 'summary' => $summary, 'description' => $summary.self::NOTE,
                'duties' => "- Görev tanımı\n- Günlük sorumluluklar", 'access_requirement' => $class === 'special' ? 'Başvuru ve görüşme' : null,
                'is_active' => true, 'sort' => $i, ...$published,
            ]);
        }

        $factions = [
            ['emniyet', 'Los Santos Emniyet', 'government', true, 'Şehrin güvenliğinden sorumlu kamu kurumu.'],
            ['saglik', 'Acil Sağlık Hizmetleri', 'government', true, 'Yaralılara müdahale eden sağlık ekipleri.'],
            ['liman-isletmesi', 'Liman İşletmesi', 'private', false, 'Lojistik ve gümrük işlerini yürüten özel şirket.'],
            ['kuzey-yakasi', 'Kuzey Yakası', 'illegal', false, 'Mahallesini kendi kurallarıyla koruyan yeraltı örgütü.'],
        ];
        foreach ($factions as $i => [$slug, $name, $type, $open, $summary]) {
            Faction::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name, 'type' => $type, 'applications_open' => $open, 'summary' => $summary,
                'description' => $summary.self::NOTE, 'min_requirements' => "- 18 saat oyun süresi\n- Akademi başlangıç dersi",
                'sort' => $i, ...$published,
            ]);
        }

        $news = NewsCategory::query()->updateOrCreate(['slug' => 'duyurular'], ['name' => 'Duyurular', 'sort' => 0]);
        $updates = NewsCategory::query()->updateOrCreate(['slug' => 'guncellemeler'], ['name' => 'Güncellemeler', 'sort' => 1]);
        $posts = [
            [$news, 'web-platformu-yayinda', 'Web platformu yayında', 'Yeni sitemiz, oyuncu paneli ve destek sistemiyle birlikte açıldı.', true],
            [$updates, 'kurallar-guncellendi', 'Roleplay kuralları güncellendi', 'RP-1 ve RP-2 maddelerine örnekler eklendi.', false],
            [$updates, 'akademi-acildi', 'Akademi açıldı', 'Yeni oyuncular için başlangıç dersi artık sitede.', false],
        ];
        foreach ($posts as $i => [$category, $slug, $title, $excerpt, $pinned]) {
            NewsPost::query()->updateOrCreate(['slug' => $slug], [
                'news_category_id' => $category->id, 'title' => $title, 'excerpt' => $excerpt, 'is_pinned' => $pinned,
                'body' => $excerpt."\n\nAyrıntılar bu bölümde yer alır.".self::NOTE,
                'status' => 'published', 'published_at' => now()->subDays($i + 1),
            ]);
        }

        CommunityEvent::query()->updateOrCreate(['slug' => 'acilis-gecesi'], [
            'title' => 'Açılış gecesi', 'summary' => 'Şehrin açılışını birlikte kutluyoruz.', 'description' => 'Program ve kurallar.'.self::NOTE,
            'starts_at' => now()->addDays(5)->setTime(20, 0), 'location_label' => 'Belediye meydanı', ...$published,
        ]);

        Cache::flush();
    }
}
