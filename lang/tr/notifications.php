<?php

declare(strict_types=1);

// Bildirim tercihleri ve Web Push (Faz 10) — docs/DATABASE.md §3.11, §3.11a.
return [
    'categories' => [
        'support' => 'Destek talebime yanıt geldiğinde',
        'announcements' => 'Duyurular (e-posta kanalı seçildiğinde)',
        'applications' => 'Başvurum karara bağlandığında veya mesaj geldiğinde',
        'sanctions' => 'Ceza ve itiraz gelişmeleri',
        'payments' => 'Ödeme ve iade sonuçları',
    ],
    'channels' => [
        'mail' => 'E-posta',
        'push' => 'Tarayıcı bildirimi',
    ],
    'push' => [
        'title' => 'Tarayıcı bildirimleri',
        'intro' => 'Bu cihazda panel bildirimlerini anlık almak için tarayıcı izni ver. İstediğin zaman kapatabilirsin.',
        'enable' => 'Bu cihazda aç',
        'disable' => 'Bu cihazda kapat',
        'on' => 'Bu cihazda açık.',
        'off' => 'Bu cihazda kapalı.',
        'denied' => 'Tarayıcı izni reddedilmiş; tarayıcı ayarlarından izin vermelisin.',
        'unsupported' => 'Bu tarayıcı veya bağlantı (HTTPS gerekir) anlık bildirimi desteklemiyor.',
        'error' => 'İşlem tamamlanamadı, tekrar dene.',
        'limit' => 'En fazla 10 cihazda tarayıcı bildirimi açılabilir. Önce eski bir cihazda kapat.',
    ],
    'realtime' => [
        'ticket' => 'Bu talepte yeni mesaj var.',
        'queue' => 'Kuyrukta yeni hareket var.',
        'reload' => 'Yenile',
    ],
];
