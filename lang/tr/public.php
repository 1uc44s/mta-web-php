<?php

declare(strict_types=1);

// Public site arayüz metinleri — docs/DESIGN.md §9 yazım kılavuzu ("sen" hitabı, fiil + nesne butonlar).

return [
    'skip_to_content' => 'İçeriğe geç',
    'menu' => 'Menü',
    'open_menu' => 'Menüyü aç',
    'close_menu' => 'Menüyü kapat',
    'main_navigation' => 'Ana menü',
    'breadcrumb' => 'Konum',
    'home' => 'Ana Sayfa',
    'read_more' => 'Devamını oku',
    'details' => 'Ayrıntılar',
    'back' => 'Geri',
    'all' => 'Tümü',
    'last_updated' => 'Son güncelleme',
    'minutes' => ':count dk',
    'example_content' => 'Örnek içerik — yönetim panelinden değiştirilecek',

    'join' => [
        'cta' => 'Oyuna Katıl',
        'how_to_play' => 'Nasıl Oynanır?',
        'title' => 'Sunucuya bağlan',
        'intro' => 'MTA:SA kuruluysa aşağıdaki butonla doğrudan bağlanabilir ya da adresi kopyalayıp oyun içinden girebilirsin.',
        'server_label' => 'Sunucu',
        'copy' => 'Adresi kopyala',
        'copied' => 'Kopyalandı',
        'open_mta' => 'MTA ile aç',
        'no_mta' => 'MTA kurulu değil mi? Adım adım kurulum rehberine göz at.',
        'guide' => 'Kurulum rehberi',
        'close' => 'Kapat',
    ],

    'theme' => [
        'label' => 'Görünüm',
        'dark' => 'Gece',
        'light' => 'Gündüz',
        'system' => 'Sistem',
        'effects' => 'Düşük efekt',
        'effects_hint' => 'Animasyonları ve arka plan hareketlerini kapatır.',
        'save' => 'Kaydet',
    ],

    'status' => [
        'players' => ':players / :max oyuncu',
        'players_short' => ':players oyuncu',
        'stale_note' => 'Son bilinen veri · :time',
        'offline_note' => 'Sunucudan güncel veri alınamıyor.',
        'live_note' => 'Canlı · :time güncellendi',
        'maintenance_note' => 'Bakım çalışması sürüyor.',
        'no_server' => 'Sunucu bilgisi henüz tanımlanmadı.',
        'card_title' => 'Sunucu durumu',
        'map' => 'Harita',
        'mode' => 'Oyun modu',
        'capacity' => 'Kapasite',
        'address' => 'Adres',
    ],

    'footer' => [
        'quick' => 'Keşfet',
        'community' => 'Topluluk',
        'legal' => 'Yasal',
        'disclaimer' => 'Bu topluluk Rockstar Games, Take-Two Interactive veya Multi Theft Auto ekibiyle bağlantılı değildir. Grand Theft Auto ve ilgili markalar sahiplerine aittir.',
        'rights' => '© :year :name. Tüm hakları saklıdır.',
        'data_controller' => 'Veri sorumlusu',
    ],

    'cookies' => [
        'title' => 'Çerez tercihin',
        'text' => 'Sitenin çalışması için zorunlu çerezler kullanıyoruz. Ziyaret istatistikleri için analitik çerezler yalnızca onay verirsen çalışır.',
        'accept' => 'Analitiğe izin ver',
        'reject' => 'Yalnızca zorunlu',
        'policy' => 'Çerez politikası',
    ],

    'maintenance_banner' => 'Bakım duyurusu',

    'pages' => [
        'systems' => ['eyebrow' => 'Oyun sistemleri', 'title' => 'Şehri ayakta tutan sistemler', 'intro' => 'Oyunda şu anda aktif olan sistemler. Burada gördüğün her şey sunucuda gerçekten çalışıyor.', 'empty' => 'Yayınlanmış oyun sistemi henüz yok. Yakında burada olacak.', 'how_it_works' => 'Nasıl çalışır?', 'how_to_access' => 'Nasıl erişirsin?', 'requirements' => 'Gereksinimler'],
        'jobs' => ['eyebrow' => 'Meslekler', 'title' => 'Şehirde ne iş yapacaksın?', 'intro' => 'Yasal meslekler, özel roller ve etkinlik görevleri.', 'empty' => 'Bu filtrede yayınlanmış meslek yok.', 'classes' => ['legal' => 'Yasal', 'special' => 'Özel', 'event' => 'Etkinlik'], 'inactive' => 'Şu an kapalı', 'duties' => 'Görevler', 'progression' => 'İlerleme', 'earnings' => 'Kazanç', 'access' => 'Erişim şartı'],
        'factions' => ['eyebrow' => 'Fraksiyonlar', 'title' => 'Bir tarafa ait ol', 'intro' => 'Kamu kurumları, yasal organizasyonlar ve yeraltı dünyası.', 'empty' => 'Bu filtrede yayınlanmış fraksiyon yok.', 'types' => ['legal' => 'Yasal', 'illegal' => 'İllegal', 'government' => 'Kamu', 'private' => 'Özel'], 'applications_open' => 'Başvurular açık', 'applications_closed' => 'Başvurular kapalı', 'requirements' => 'Minimum gereksinimler', 'apply' => 'Başvur', 'apply_soon' => 'Panel üzerinden başvuru yakında açılacak.', 'rules' => 'Fraksiyon kuralları'],
        'start' => ['eyebrow' => 'Nasıl oynanır', 'title' => 'İlk bağlantına giden rota', 'intro' => 'Yasal bir oyun kopyasından ilk karakterine kadar adım adım. Takıldığın yerde destek ekibi yanında.', 'empty' => 'Kurulum rehberi hazırlanıyor.', 'step' => 'Adım :number', 'official_only' => 'Yalnızca resmi kaynaklardan indir. Değiştirilmiş oyun arşivleri dağıtmıyor ve önermiyoruz.'],
        'rules' => ['eyebrow' => 'Kurallar', 'title' => 'Şehrin kuralları', 'intro' => 'Herkesin keyifle oynayabilmesi için ortak zemin. Her kurala kodundan bağlantı verebilirsin.', 'search' => 'Kurallarda ara', 'search_placeholder' => 'Kural kodu veya kelime…', 'no_results' => 'Aramanla eşleşen kural yok.', 'version' => 'Kural seti sürümü', 'copy_link' => 'Bağlantıyı kopyala', 'empty' => 'Kurallar yayınlanmak üzere hazırlanıyor.', 'contents' => 'Bölümler'],
        'academy' => ['eyebrow' => 'Akademi', 'title' => 'Roleplay akademisi', 'intro' => 'Kuralları ve şehrin kültürünü sunucuya girmeden öğren. Zorunlu dersler yeni oyuncular için işaretlidir.', 'empty' => 'Akademi dersleri hazırlanıyor.', 'required' => 'Zorunlu', 'topics' => ':count konu', 'levels' => ['beginner' => 'Başlangıç', 'intermediate' => 'Orta', 'advanced' => 'İleri'], 'start_course' => 'Derse başla', 'previous' => 'Önceki konu', 'next' => 'Sonraki konu', 'related_rules' => 'İlgili kurallar', 'topic_of' => 'Konu :current / :total'],
        'news' => ['eyebrow' => 'Haberler', 'title' => 'Şehirden haberler', 'intro' => 'Güncellemeler, duyurular ve topluluktan gelişmeler.', 'empty' => 'Henüz yayınlanmış haber yok.', 'pinned' => 'Sabit', 'related' => 'İlgili haberler', 'by' => ':author tarafından'],
        'events' => ['eyebrow' => 'Etkinlikler', 'title' => 'Takvimdeki buluşmalar', 'intro' => 'Topluluk etkinlikleri ve özel oturumlar.', 'upcoming' => 'Yaklaşan', 'past' => 'Geçmiş', 'empty' => 'Planlanmış etkinlik yok. Duyurular için Discord’u takip et.', 'location' => 'Konum'],
        'faq' => ['eyebrow' => 'SSS', 'title' => 'Sık sorulan sorular', 'intro' => 'Aradığın cevabı bulamazsan bize yaz.', 'empty' => 'Sık sorulan sorular hazırlanıyor.', 'groups' => ['baslangic' => 'Başlangıç', 'hesap' => 'Hesap ve giriş', 'oyun' => 'Oyun', 'destek' => 'Destek', 'odeme' => 'Ödeme']],
        'about' => ['eyebrow' => 'Hakkımızda'],
        'legal' => ['eyebrow' => 'Yasal', 'version' => 'Sürüm :version', 'effective' => 'Yürürlük: :date', 'draft_notice' => 'Bu metin taslaktır ve hukuki kontrol bekliyor.'],
    ],

    'contact' => [
        'eyebrow' => 'İletişim',
        'title' => 'Bize yaz',
        'intro' => 'Genel sorular, iş birlikleri ve geri bildirimler için. Hesabınla ilgili sorunlar için oyuncu panelindeki destek sistemi daha hızlı sonuç verir.',
        'send' => 'Mesajı gönder',
        'sent' => 'Mesajın bize ulaştı. Teşekkürler!',
        'response_time' => 'Yanıt süresi',
        'discord' => 'Discord topluluğu',
        'privacy' => 'Gönderdiğin bilgiler yalnızca bu mesajı yanıtlamak için kullanılır.',
        'attributes' => ['name' => 'Adın', 'email' => 'E-posta adresin', 'subject' => 'Konu', 'body' => 'Mesajın'],
        'hints' => ['body' => 'En az 20 karakter.'],
    ],

    'home_sections' => [
        // v2 bölüm metinleri (dev arka plan kelimeleri dekoratiftir, ekran okuyucuya okunmaz).
        'all_systems' => 'Tüm sistemler',
        'choose_ghost' => 'Seçim',
        'start_ghost' => 'Başla',
        'or' => 'ya da',
        'stats' => [
            'registered_users' => 'Kayıtlı oyuncu',
            'factions_count' => 'Aktif fraksiyon',
            'systems_count' => 'Oyunda aktif sistem',
        ],
        'all_news' => 'Tüm haberler',
        'all_events' => 'Tüm etkinlikler',
        'all_faq' => 'Tüm sorular',
        'discord_join' => 'Discord’a katıl',
    ],
];
