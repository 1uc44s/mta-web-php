<?php

declare(strict_types=1);

/*
 * Proje sabitleri. Güvenlik sınırını belirleyen değerler buradadır ve ACP'den değiştirilemez
 * (docs/ARCHITECTURE.md §14). Çalışma zamanında değiştirilebilen ayarlar system_settings tablosundadır.
 */

return [

    'brand' => [
        'name' => env('APP_NAME', 'Meridyen Roleplay'),
        'short_name' => 'Meridyen',
    ],

    // Sunum saat dilimi; DB ve uygulama UTC çalışır (docs/SRS.md C-04).
    'display_timezone' => 'Europe/Istanbul',

    // Hesap bağlama kodu (docs/SRS.md FR-ACC-001..003).
    'link_code' => [
        'length' => 8,
        'alphabet' => '0123456789ABCDEFGHJKMNPQRSTVWXYZ', // Crockford Base32 (I, L, O, U yok)
        'ttl_minutes' => 10,
        'max_attempts' => 5,
    ],

    // MTA köprüsü (docs/ARCHITECTURE.md §7, docs/SRS.md FR-BRG-*).
    'bridge' => [
        'timestamp_window_seconds' => 300,
        'nonce_ttl_minutes' => 15,
        'max_body_kilobytes' => 64,
        'serial_pepper' => env('BRIDGE_SERIAL_PEPPER'),
        'command_result_timeout_seconds' => 120,
        'command_max_attempts' => 3,

        // Komut türü => gerekli izin (docs/ARCHITECTURE.md §7.5). null = yalnızca sistem.
        'commands' => [
            'player.warn' => 'game_commands.warn',
            'player.kick' => 'game_commands.kick',
            'player.mute' => 'game_commands.kick',
            'player.jail' => 'game_commands.ban_temp',
            'player.ban' => 'game_commands.ban_temp', // süresiz ban ayrıca game_commands.ban_perm ister
            'player.unban' => 'game_commands.ban_temp',
            'server.announce' => 'game_commands.announce',
            'account.link_revoked' => null,
        ],
    ],

    // Sunucu durumu eşikleri (docs/ARCHITECTURE.md §7.8).
    'server_status' => [
        'fresh_seconds' => 90,
        'offline_after_seconds' => 300,
        'public_cache_seconds' => 15,
    ],

    // Web'de gösterilebilecek karakter alanları (docs/SRS.md FR-ACC-009).
    'character_public_fields' => ['bio', 'job_label', 'faction_label', 'level', 'playtime_hours'],

    // docs/SRS.md FR-AUTH-001: kayıtta kullanılamayan kullanıcı adları (büyük/küçük harf duyarsız karşılaştırılır).
    'reserved_usernames' => [
        'admin', 'administrator', 'root', 'system', 'sistem', 'support', 'destek', 'moderator', 'moderatör',
        'yonetim', 'yönetim', 'staff', 'yetkili', 'meridyen', 'meridyenrp', 'mrp', 'official', 'resmi',
        'null', 'undefined', 'api', 'panel', 'login', 'register',
    ],

    'payments' => [
        'provider' => env('PAYMENTS_PROVIDER', 'fake'),
    ],

    'pwa' => [
        'register_in_dev' => (bool) env('PWA_DEV', false),
    ],

];
