<?php

declare(strict_types=1);

/*
 * Sistem ayarları kataloğu ve varsayılanları — docs/ADMIN.md §7.
 * DB'de kayıt yoksa buradaki varsayılan kullanılır. 'super' = yalnızca süper admin değiştirebilir.
 */

return [
    'site.name' => ['group' => 'site', 'type' => 'string', 'default' => 'Meridyen Roleplay', 'public' => true],
    'site.short_name' => ['group' => 'site', 'type' => 'string', 'default' => 'Meridyen', 'public' => true],
    'site.tagline' => ['group' => 'site', 'type' => 'string', 'default' => 'Şehrin ritmi, senin hikâyen.', 'public' => true],
    'site.description' => ['group' => 'site', 'type' => 'string', 'default' => 'MTA:SA üzerinde sesli, kurallı ve yaşayan bir roleplay şehri. Hesabını aç, kuralları öğren, hikâyeni yaz.', 'public' => true],
    // ACP → Marka ekranından yönetilir ('managed': genel ayar formunda görünmez) — docs/DESIGN.md §2.
    'site.logo_path' => ['group' => 'site', 'type' => 'string', 'default' => null, 'public' => true, 'managed' => true],
    'site.icon_set' => ['group' => 'site', 'type' => 'string', 'default' => null, 'public' => true, 'managed' => true],
    'site.og_default_media_id' => ['group' => 'site', 'type' => 'int', 'default' => null, 'public' => true],
    'site.contact_email' => ['group' => 'site', 'type' => 'string', 'default' => null, 'public' => true],
    'site.data_controller' => ['group' => 'site', 'type' => 'json', 'default' => null, 'public' => true],

    'server.primary_slug' => ['group' => 'server', 'type' => 'string', 'default' => 'main', 'public' => true],
    'server.show_player_count' => ['group' => 'server', 'type' => 'bool', 'default' => true, 'public' => true],

    'social.discord_invite' => ['group' => 'social', 'type' => 'url', 'default' => null, 'public' => true],
    'social.youtube_url' => ['group' => 'social', 'type' => 'url', 'default' => null, 'public' => true],

    'features.registration' => ['group' => 'features', 'type' => 'bool', 'default' => true, 'public' => true],
    'features.wallet' => ['group' => 'features', 'type' => 'bool', 'default' => false, 'public' => true],
    'features.payments' => ['group' => 'features', 'type' => 'bool', 'default' => false, 'public' => true, 'super' => true],
    'features.applications' => ['group' => 'features', 'type' => 'bool', 'default' => false, 'public' => true],
    'features.sanctions' => ['group' => 'features', 'type' => 'bool', 'default' => false, 'public' => true],
    'features.devices' => ['group' => 'features', 'type' => 'bool', 'default' => false, 'public' => true],
    'features.events' => ['group' => 'features', 'type' => 'bool', 'default' => true, 'public' => true],
    'features.pwa' => ['group' => 'features', 'type' => 'bool', 'default' => true, 'public' => true],
    'features.push' => ['group' => 'features', 'type' => 'bool', 'default' => false, 'public' => true],
    'features.academy_quiz' => ['group' => 'features', 'type' => 'bool', 'default' => false, 'public' => true],
    'discord.announcements' => ['group' => 'discord', 'type' => 'bool', 'default' => false, 'public' => false],
    'discord.ops_alerts' => ['group' => 'discord', 'type' => 'bool', 'default' => false, 'public' => false],

    'accounts.max_links_per_user' => ['group' => 'accounts', 'type' => 'int', 'default' => 3, 'public' => false],

    'support.max_attachments' => ['group' => 'support', 'type' => 'int', 'default' => 3, 'public' => false],
    'support.auto_resolve_days' => ['group' => 'support', 'type' => 'int', 'default' => 7, 'public' => false],
    'support.auto_close_days' => ['group' => 'support', 'type' => 'int', 'default' => 7, 'public' => false],
    'support.expected_response_text' => ['group' => 'support', 'type' => 'string', 'default' => 'Genellikle 24 saat içinde yanıtlıyoruz.', 'public' => true],

    'wallet.four_eyes_threshold' => ['group' => 'wallet', 'type' => 'int', 'default' => 500, 'public' => false],
    'wallet.allow_self_apply_below_threshold' => ['group' => 'wallet', 'type' => 'bool', 'default' => true, 'public' => false],

    'maintenance.banner_enabled' => ['group' => 'maintenance', 'type' => 'bool', 'default' => false, 'public' => true],
    'maintenance.banner_text' => ['group' => 'maintenance', 'type' => 'string', 'default' => null, 'public' => true],
    'maintenance.full' => ['group' => 'maintenance', 'type' => 'bool', 'default' => false, 'public' => false],

    'rules.current_version' => ['group' => 'rules', 'type' => 'string', 'default' => '2026.11.0', 'public' => true],
    'rules.change_note' => ['group' => 'rules', 'type' => 'string', 'default' => null, 'public' => true],

    'analytics.enabled' => ['group' => 'analytics', 'type' => 'bool', 'default' => false, 'public' => true],

    'security.admin_ip_allowlist' => ['group' => 'security', 'type' => 'json', 'default' => [], 'public' => false, 'super' => true],
    'security.new_login_alerts' => ['group' => 'security', 'type' => 'bool', 'default' => true, 'public' => false, 'super' => true],

    'mail.from_name' => ['group' => 'mail', 'type' => 'string', 'default' => 'Meridyen Roleplay', 'public' => false, 'super' => true],
];
