<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\AcademyCourse;
use App\Models\Faq;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Rule;
use App\Models\RuleCategory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Sistem içerikleri (docs/DATABASE.md §12): ana sayfa bölümleri, yasal/hakkımızda sayfaları, SSS, kurallar,
 * Akademi "Başlangıç" dersi, navigasyon. İdempotenttir (slug/kod ile updateOrCreate).
 * Yerelde yayında, production'da taslak olarak oluşturulur (içerik ekibi gözden geçirip yayınlar).
 */
final class ContentSeeder extends Seeder
{
    private ContentStatus $status;

    public function run(): void
    {
        $this->status = app()->environment(['local', 'testing']) ? ContentStatus::Published : ContentStatus::Draft;

        $this->homePage();
        $this->staticPages();
        $this->faqs();
        $this->rules();
        $this->onboardingCourse();
        $this->navigation();

        Cache::flush();
    }

    /** @return array{status: string, published_at: CarbonImmutable|null} */
    private function publication(): array
    {
        return [
            'status' => $this->status->value,
            'published_at' => $this->status === ContentStatus::Published ? now()->subMinute() : null,
        ];
    }

    private function homePage(): void
    {
        $page = Page::query()->updateOrCreate(['slug' => 'home'], [
            'title' => 'Ana Sayfa', 'template' => 'home', 'is_system' => true,
            'meta_description' => 'MTA:SA üzerinde sesli, kurallı ve yaşayan bir roleplay şehri. Hesabını aç, kuralları öğren, hikâyeni yaz.',
            ...$this->publication(),
        ]);

        $sections = [
            ['hero', [
                'eyebrow' => 'San Andreas · Roleplay',
                'title' => 'Şehrin ritmi,',
                'title_accent' => 'senin hikâyen.',
                'pillars' => [
                    ['title' => 'Sesli', 'text' => 'Yakınlık tabanlı ses ve telsiz'],
                    ['title' => 'Kurallı', 'text' => 'Yazılı, adil ve şeffaf kurallar'],
                    ['title' => 'Yaşayan', 'text' => 'Meslekler, ekonomi ve fraksiyonlar'],
                ],
                'lead' => '{site_short}, MTA:SA üzerinde sesli iletişimin, kuralların ve karakter gelişiminin önemsendiği bir roleplay şehri. Bir meslek seç, bir tarafa katıl ya da kendi yolunu çiz.',
            ]],
            ['feature_grid', [
                'eyebrow' => 'Şehirde neler var',
                'title' => 'Yaşayan bir şehir için sistemler',
                'intro' => 'Aşağıdaki kartlar oyunda aktif olan sistemlere bağlanır; henüz açılmamış bir sistem burada görünmez.',
                'variant' => 'grid-3',
                'items' => [
                    ['icon' => 'mic', 'title' => 'Sesli roleplay', 'text' => 'Yakınlık tabanlı sesli iletişim; telsiz ve telefonla uzak mesafe.', 'system_slug' => 'sesli-iletisim'],
                    ['icon' => 'briefcase', 'title' => 'Meslekler ve ekonomi', 'text' => 'Emeğinle kazan, birikimini işletmene ya da evine yatır.', 'system_slug' => 'ekonomi'],
                    ['icon' => 'car', 'title' => 'Araç ve garaj', 'text' => 'Ruhsat, sigorta ve modifiye; aracın senin sorumluluğunda.', 'system_slug' => 'arac-ve-garaj'],
                    ['icon' => 'house', 'title' => 'Ev ve mülk', 'text' => 'Mülk edin, kirala, düzenle; şehirde kalıcı bir iz bırak.', 'system_slug' => 'ev-ve-mulk'],
                    ['icon' => 'shield', 'title' => 'Fraksiyonlar', 'text' => 'Kamu kurumlarından yeraltı örgütlerine kadar organize roller.', 'system_slug' => 'fraksiyonlar'],
                    ['icon' => 'trending-up', 'title' => 'Karakter gelişimi', 'text' => 'Beceriler, itibar ve geçmiş; karakterin oynadıkça derinleşir.', 'system_slug' => 'karakter-gelisimi'],
                ],
            ]],
            ['lifestyle_split', [
                'eyebrow' => 'İki taraf',
                'title' => 'Hangi tarafta duracaksın?',
                'columns' => [
                    ['label' => 'Yasal yaşam', 'title' => 'Kurumlar, meslekler, girişimler', 'text' => 'Polis, sağlık ekipleri, ulaşım ya da kendi işletmen. Şehri ayakta tutanlardan biri ol.', 'link_label' => 'Yasal meslekler', 'link_route' => 'public.jobs.index', 'link_params' => ['class' => 'legal']],
                    ['label' => 'Yeraltı', 'title' => 'Örgütler, pazarlıklar, riskler', 'text' => 'Gölgelerde kurulan ittifaklar. Kurallar burada da geçerli; ağır roleplay sorumluluk ister.', 'link_label' => 'Fraksiyonlara göz at', 'link_route' => 'public.factions.index', 'link_params' => ['type' => 'illegal']],
                ],
            ]],
            ['onboarding_steps', [
                'eyebrow' => 'İlk rota',
                'title' => 'Beş adımda şehre gir',
                'intro' => 'Kurulumdan ilk karakterine kadar her adımın ayrıntısı rehberde.',
                'steps' => [
                    ['title' => 'Oyunu edin', 'text' => 'GTA: San Andreas’ın yasal bir kopyası gerekir.'],
                    ['title' => 'MTA:SA’yı kur', 'text' => 'Yalnızca resmi multitheftauto.com adresinden.'],
                    ['title' => 'Kuralları oku', 'text' => 'Akademi’deki başlangıç dersi 10 dakikanı alır.'],
                    ['title' => 'Sunucuya bağlan', 'text' => 'Adresi kopyala ya da “MTA ile aç”.'],
                    ['title' => 'Karakterini yarat', 'text' => 'Bir geçmiş, bir hedef ve ilk sahnen.'],
                ],
            ]],
            ['stats_strip', ['title' => 'Sayılarla {site_short}', 'metrics' => ['registered_users', 'factions_count', 'systems_count']]],
            ['news_latest', ['eyebrow' => 'Haberler', 'title' => 'Şehirden son gelişmeler', 'count' => 3]],
            ['events_upcoming', ['eyebrow' => 'Etkinlikler', 'title' => 'Takvimde neler var?', 'count' => 3]],
            ['community', ['eyebrow' => 'Topluluk', 'title' => 'Hikâyeler Discord’da da devam ediyor', 'intro' => 'Duyurular, etkinlik çağrıları ve yeni oyunculara yardım eden bir topluluk.']],
            ['faq_list', ['eyebrow' => 'SSS', 'title' => 'Aklındaki sorular']],
            ['cta_banner', ['eyebrow' => 'Hazır mısın?', 'title' => 'Şehir seni bekliyor', 'text' => 'Kurulumu tamamla, kuralları oku ve ilk sahneni oynamak için bağlan.']],
        ];

        $page->sections()->delete();
        foreach ($sections as $index => [$type, $data]) {
            $page->sections()->create(['type' => $type, 'data' => $data, 'sort' => $index, 'is_visible' => true]);
        }
    }

    private function staticPages(): void
    {
        $draft = "> **Taslak:** Bu metin örnek yapıdadır ve hukuki kontrol bekliyor (docs/PRD.md OD-08). Yayına almadan önce gerçek veri akışına göre güncellenmelidir.\n\n";

        $pages = [
            'about' => ['Hakkımızda', 'standard', "## Neden {site_short}?\n\n{site_short}, oyuncuların karakterlerine ve birbirlerine saygı duyduğu, kuralların herkes için aynı işlediği bir roleplay şehri olarak kuruldu. Amacımız kısa süreli kaos değil, uzun soluklu hikâyeler.\n\n## Roleplay yaklaşımımız\n\n- **Karakter önce gelir.** Kararlarını karakterinin geçmişi ve hedefleri belirler.\n- **Sonuçlar gerçektir.** Yaptıklarının şehirde kalıcı etkileri olur.\n- **Herkes sahnenin parçası.** Kazanmak değil, iyi bir sahne kurmak önemlidir.\n\n## Topluluk değerlerimiz\n\nSaygı, şeffaflık ve tutarlılık. Yönetim kararları kayıt altındadır, cezalar gerekçelidir ve itiraz edilebilir.\n\n## Geliştirme yaklaşımı\n\nOyunda olmayan bir özelliği varmış gibi göstermeyiz. Bu sitede gördüğün sistemler sunucuda gerçekten çalışır."],
            'privacy' => ['Gizlilik Politikası', 'legal', $draft."## Hangi verileri işliyoruz?\n\nKullanıcı adı, e-posta adresi, parola özeti (parolanın kendisi değil), oturum ve güvenlik kayıtları, bağlı oyun hesabı referansları ile destek ve başvuru içerikleri.\n\n## Ne amaçla?\n\nHesabının güvenliği, oyun hesabınla eşleştirme, destek taleplerinin yanıtlanması ve yasal yükümlülükler.\n\n## Saklama süreleri\n\nGüvenlik kayıtları 180 gün, kapatılmış destek talepleri 2 yıl saklanır. Ayrıntılar KVKK aydınlatma metnindedir."],
            'kvkk' => ['KVKK Aydınlatma Metni', 'legal', $draft."## Veri sorumlusu\n\nVeri sorumlusu bilgileri yayından önce bu bölüme eklenecektir.\n\n## İşleme amaçları ve hukuki sebepler\n\nKişisel verilerin, üyelik sözleşmesinin kurulması ve ifası, bilgi güvenliğinin sağlanması ve yasal yükümlülüklerin yerine getirilmesi amaçlarıyla işlenir.\n\n## Haklarınız\n\n6698 sayılı Kanun'un 11. maddesi kapsamındaki haklarını kullanmak için iletişim sayfasındaki kanaldan başvurabilirsin."],
            'terms' => ['Kullanım Koşulları', 'legal', $draft."## Hesap\n\nHesabının güvenliğinden sen sorumlusun. Hesabını başkasıyla paylaşmamalısın.\n\n## Davranış\n\nSunucu ve topluluk kurallarına uymayı kabul edersin. Kural ihlalleri ceza ile sonuçlanabilir; cezalar gerekçelidir ve itiraz edilebilir.\n\n## Değişiklikler\n\nBu koşullar güncellenebilir; önemli değişiklikler panel üzerinden duyurulur."],
            'refund-policy' => ['İade Politikası', 'legal', $draft."## Kapsam\n\nBu politika web panelinden satın alınan {site_short} Kredisi (MK) paketleri için geçerlidir.\n\n## İade koşulları\n\nKullanılmamış krediler için ödeme tarihinden itibaren 14 gün içinde destek talebiyle iade isteyebilirsin. İade, ödemenin yapıldığı yönteme yapılır ve hesabından ilgili kredi düşülür; kredi harcanmışsa iade yapılamaz.\n\n## Süreç\n\nİade talepleri iki ayrı yetkilinin onayıyla işlenir. Sonuç panelde bildirim olarak gösterilir.\n\n## Oyun içi değer\n\nKrediler yalnızca web hesabında kullanılır; nakde veya oyun içi paraya çevrilemez."],
            'cookies' => ['Çerez Politikası', 'legal', $draft."## Zorunlu çerezler\n\nOturum, güvenlik (CSRF) ve tercih (tema, çerez onayı) çerezleri sitenin çalışması için gereklidir.\n\n## Analitik çerezler\n\nYalnızca açık onay verirsen çalışır. Tercihini sayfanın altındaki çerez bildiriminden değiştirebilirsin."],
        ];

        foreach ($pages as $slug => [$title, $template, $body]) {
            Page::query()->updateOrCreate(['slug' => $slug], [
                'title' => $title, 'template' => $template, 'body' => $body, 'is_system' => true,
                'version_label' => $template === 'legal' ? '2026-11-11-taslak' : null,
                'effective_at' => $template === 'legal' ? now() : null,
                ...$this->publication(),
            ]);
        }
    }

    private function faqs(): void
    {
        $faqs = [
            ['baslangic', 'Sunucuya nasıl katılırım?', 'Önce GTA: San Andreas’ın yasal bir kopyasını ve resmi MTA:SA istemcisini kur. Ardından ana sayfadaki **Oyuna Katıl** butonuyla adresi kopyala ya da doğrudan MTA ile aç. Adım adım anlatım için [kurulum rehberine](/start) bak.', true],
            ['baslangic', 'Hangi MTA sürümü gerekiyor?', 'Her zaman multitheftauto.com adresindeki güncel kararlı sürümü kullan. Sunucu eski istemcileri kabul etmeyebilir.', true],
            ['oyun', 'Sesli konuşma zorunlu mu?', 'Roleplay sesli iletişim üzerine kurulu. Mikrofonun yoksa sahnelere katılabilirsin, ancak birçok meslek ve fraksiyon sesli iletişim ister.', true],
            ['hesap', 'Web hesabı ile oyun hesabı aynı mı?', 'Hayır. Web hesabın panel ve destek içindir; oyun hesabın sunucuda oluşturulur. İkisini panelden tek kullanımlık bir kodla güvenle bağlarsın.', true],
            ['hesap', 'Şifremi unuttum, ne yapmalıyım?', 'Giriş ekranındaki “Şifremi unuttum” bağlantısıyla e-postana sıfırlama bağlantısı iste. Bağlantı 60 dakika geçerlidir.', false],
            ['destek', 'Karakterimle ilgili destek talebini nereden açarım?', 'Oyuncu panelindeki **Destek** bölümünden kategori seçerek talep açabilirsin. Talebinin durumunu oradan takip edersin.', true],
            ['oyun', 'Kurallara nereden ulaşabilirim?', 'Tüm kurallar [kurallar sayfasında](/rules). Her kuralın bir kodu vardır; yetkililer cezalarda bu koda atıf yapar.', false],
        ];

        foreach ($faqs as $index => [$group, $question, $answer, $home]) {
            Faq::query()->updateOrCreate(['question' => $question], [
                'group' => $group, 'answer' => $answer, 'show_on_home' => $home, 'sort' => $index, ...$this->publication(),
            ]);
        }
    }

    private function rules(): void
    {
        $categories = [
            ['genel', 'GNL', 'Genel kurallar', 'Sunucuda ve toplulukta herkes için geçerli temel kurallar.', [
                ['GNL-1', 'Saygı', 'Oyunculara ve yetkililere karşı hakaret, ayrımcılık ve taciz yasaktır. Bu kural karakter dışı (OOC) tüm iletişim için geçerlidir.'],
                ['GNL-2', 'Hile ve istismar', 'Hile yazılımı, oyun açıklarının istismarı ve üçüncü taraf avantaj araçları kalıcı uzaklaştırma sebebidir. Bulduğun açığı destek üzerinden bildir.'],
                ['GNL-3', 'Hesap güvenliği', 'Hesabını paylaşma, satma veya devretme. Hesabınla yapılan işlemlerden sen sorumlusun.'],
            ]],
            ['roleplay', 'RP', 'Roleplay kuralları', 'Sahnelerin tutarlı ve keyifli kalması için.', [
                ['RP-1', 'Karakter içi ve dışı (IC/OOC)', 'Karakter dışında öğrendiğin bilgiyi karakter içinde kullanma (metagaming). OOC iletişimi yalnızca gerektiğinde ve uygun kanaldan yap.'],
                ['RP-2', 'Powergaming', 'Karşı tarafa tepki şansı tanımayan, gerçek dışı veya zorla sonuç dayatan eylemler yasaktır.'],
                ['RP-3', 'Değer verme (Fear RP)', 'Karakterin hayatına değer vermelidir. Silah doğrultulmuşken umursamaz davranmak kuraldışıdır.'],
                ['RP-4', 'Sahne terk etme', 'Devam eden bir sahneden oyundan çıkarak kaçmak yasaktır. Zorunlu bir durumda karşı tarafa OOC bildir.'],
            ]],
            ['sesli', 'SES', 'Sesli iletişim', 'Mikrofon kullanımı ve ortam düzeni.', [
                ['SES-1', 'Ortam gürültüsü', 'Sürekli arka plan gürültüsü, müzik çalma veya bağırma sahneyi bozar; bas-konuş kullanmanı öneririz.'],
                ['SES-2', 'Ses değiştirici', 'Karakter için ses değiştirici kullanımı yönetim onayına tabidir.'],
            ]],
        ];

        foreach ($categories as $catIndex => [$slug, $prefix, $name, $description, $rules]) {
            $category = RuleCategory::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name, 'prefix' => $prefix, 'description' => $description, 'sort' => $catIndex, ...$this->publication(),
            ]);
            foreach ($rules as $ruleIndex => [$code, $title, $body]) {
                Rule::query()->updateOrCreate(['code' => $code], [
                    'rule_category_id' => $category->id, 'title' => $title, 'body' => $body, 'sort' => $ruleIndex, ...$this->publication(),
                ]);
            }
        }
    }

    private function onboardingCourse(): void
    {
        $course = AcademyCourse::query()->updateOrCreate(['slug' => 'baslangic'], [
            'title' => 'Başlangıç', 'summary' => 'Kurulumdan ilk karakterine: şehre giriş rotası.', 'level' => 'beginner',
            'is_required' => true, 'is_onboarding' => true, 'estimated_minutes' => 15, 'sort' => 0, ...$this->publication(),
        ]);

        $topics = [
            ['oyunu-edin', 'Oyunu edin', 'Oyun', "MTA:SA, **Grand Theft Auto: San Andreas**'ın bilgisayarında kurulu olmasını ister. Oyunu yasal bir dijital mağazadan veya orijinal kopyandan kur.\n\nDeğiştirilmiş oyun arşivleri (repack) hem güvenlik riski taşır hem de MTA ile sorun çıkarabilir. Bu tür dosyaları dağıtmıyor ve önermiyoruz."],
            ['mta-kurulumu', 'MTA:SA’yı kur', 'MTA kurulumu', "1. Yalnızca resmi adresi kullan: [multitheftauto.com](https://multitheftauto.com).\n2. Kurulum sırasında GTA: San Andreas'ın kurulu olduğu klasörü seç.\n3. Kurulum bittiğinde MTA'yı bir kez başlatıp güncellemelerin tamamlanmasını bekle."],
            ['kurallari-oku', 'Kuralları oku', 'Kurallar', "Sunucuya girmeden önce [kurallar sayfasını](/rules) oku. Özellikle **RP-1** (IC/OOC) ve **RP-2** (powergaming) yeni oyuncuların en sık karşılaştığı kurallardır.\n\nAkademi'deki diğer dersler roleplay temellerini daha ayrıntılı anlatır."],
            ['baglan', 'Sunucuya bağlan', 'Bağlantı', "Sitedeki **Oyuna Katıl** butonuna bas. MTA kuruluysa **MTA ile aç** seni doğrudan sunucuya götürür. Olmazsa adresi kopyala ve MTA'da *Hızlı Bağlan* ekranına yapıştır."],
            ['karakter', 'Karakterini yarat', 'Karakter', "İyi bir karakterin bir geçmişi, bir hedefi ve bir zaafı vardır. Adını gerçekçi seç, şehre neden geldiğini düşün ve ilk sahnende kendini tanıtmaktan çekinme.\n\nTakıldığın her yerde oyuncu panelinden destek talebi açabilirsin."],
        ];

        foreach ($topics as $index => [$slug, $title, $label, $body]) {
            $course->topics()->updateOrCreate(['slug' => $slug], [
                'title' => $title, 'step_label' => $label, 'body' => $body, 'estimated_minutes' => 3, 'sort' => $index, ...$this->publication(),
            ]);
        }

        $course->topics()->where('slug', 'kurallari-oku')->first()?->rules()
            ->sync(Rule::query()->whereIn('code', ['RP-1', 'RP-2'])->pluck('id'));
    }

    private function navigation(): void
    {
        NavigationItem::query()->delete();

        $items = [
            'header' => [
                ['Sunucu', 'public.about'], ['Sistemler', 'public.systems.index'], ['Meslekler', 'public.jobs.index'],
                ['Fraksiyonlar', 'public.factions.index'], ['Nasıl Oynanır?', 'public.start'], ['Kurallar', 'public.rules'],
                ['Haberler', 'public.news.index'],
            ],
            'footer_quick' => [
                ['Nasıl Oynanır?', 'public.start'], ['Akademi', 'public.academy.index'], ['Oyun sistemleri', 'public.systems.index'],
                ['Kurallar', 'public.rules'], ['SSS', 'public.faq'],
            ],
            'footer_community' => [
                ['Haberler', 'public.news.index'], ['Etkinlikler', 'public.events.index'], ['İletişim', 'public.contact'], ['Hakkımızda', 'public.about'],
            ],
            'footer_legal' => [
                ['Gizlilik', 'public.legal.privacy'], ['KVKK', 'public.legal.kvkk'], ['Kullanım Koşulları', 'public.legal.terms'], ['Çerezler', 'public.legal.cookies'],
            ],
        ];

        foreach ($items as $location => $links) {
            foreach ($links as $index => [$label, $route]) {
                NavigationItem::query()->create(['location' => $location, 'label' => $label, 'route_name' => $route, 'sort' => $index]);
            }
        }
    }
}
