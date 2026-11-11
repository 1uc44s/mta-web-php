<?php

declare(strict_types=1);

/*
 * Kodda gömülü VARSAYILAN mesaj şablonları (docs/CMS.md §6.4, ROADMAP 3.7).
 * Faz 5'te message_templates tablosu birincil kaynak olur; bunlar yedek (fallback) olarak kalır.
 * Söz dizimi: {{ değişken.yolu }} — yalnızca anahtarın `variables` listesindekiler kullanılabilir.
 */

return [
    'salutation' => 'Meridyen ekibi',

    'auth.verify_email' => [
        'variables' => ['user.username', 'link.verify', 'expires_minutes'],
        'subject' => 'E-posta adresini doğrula',
        'greeting' => 'Merhaba {{ user.username }},',
        'lines' => [
            'Hesabını etkinleştirmek için e-posta adresini doğrula.',
        ],
        'action' => ['label' => 'E-postamı doğrula', 'url' => 'link.verify'],
        'outro' => [
            'Bağlantı {{ expires_minutes }} dakika geçerlidir. Bu kaydı sen yapmadıysan bu e-postayı yok sayabilirsin.',
        ],
    ],

    'auth.already_registered' => [
        'variables' => ['link.login', 'link.reset'],
        'subject' => 'Bu e-posta ile zaten bir hesabın var',
        'greeting' => 'Merhaba,',
        'lines' => [
            'Bu e-posta adresiyle yeni bir hesap oluşturulmaya çalışıldı, ancak adresin zaten kayıtlı.',
        ],
        'action' => ['label' => 'Giriş yap', 'url' => 'link.login'],
        'outro' => [
            'Parolanı hatırlamıyorsan sıfırlayabilirsin: {{ link.reset }}',
            'Bu işlemi sen yapmadıysan bir şey yapmana gerek yok.',
        ],
    ],

    'auth.password_reset' => [
        'variables' => ['user.username', 'link.reset', 'expires_minutes'],
        'subject' => 'Parola sıfırlama isteği',
        'greeting' => 'Merhaba {{ user.username }},',
        'lines' => [
            'Hesabın için bir parola sıfırlama isteği aldık.',
        ],
        'action' => ['label' => 'Yeni parola belirle', 'url' => 'link.reset'],
        'outro' => [
            'Bağlantı {{ expires_minutes }} dakika geçerlidir ve yalnızca bir kez kullanılabilir.',
            'Bu isteği sen yapmadıysan parolan değişmez; bu e-postayı yok sayabilirsin.',
        ],
    ],

    'security.password_changed' => [
        'variables' => ['user.username', 'event.time', 'event.device', 'link.security'],
        'subject' => 'Parolan değiştirildi',
        'greeting' => 'Merhaba {{ user.username }},',
        'lines' => [
            'Hesabının parolası {{ event.time }} tarihinde değiştirildi ({{ event.device }}). Diğer tüm cihazlardaki oturumların kapatıldı.',
        ],
        'action' => ['label' => 'Hesabımı kontrol et', 'url' => 'link.security'],
        'outro' => [
            'Bu değişikliği sen yapmadıysan hemen “Şifremi unuttum” ile parolanı sıfırla ve destek ekibine yaz.',
        ],
    ],
];
