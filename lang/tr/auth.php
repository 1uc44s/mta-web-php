<?php

declare(strict_types=1);

// Kimlik doğrulama metinleri. Hata mesajları hesap varlığını ifşa etmez (docs/SRS.md SEC-005).

return [
    'failed' => 'Giriş bilgileri eşleşmedi. Kullanıcı adını/e-postanı ve parolanı kontrol et.',
    'password' => 'Parola hatalı.',
    'throttle' => 'Çok fazla deneme yaptın. :seconds saniye sonra tekrar dene.',

    'suspended' => 'Hesabın askıya alındı. Gerekçe: :reason',
    'suspended_until' => 'Hesabın :date tarihine kadar askıya alındı. Gerekçe: :reason',
    'session_ended_suspended' => 'Hesabının durumu değiştiği için oturumun kapatıldı.',
    'logged_out' => 'Güvenli şekilde çıkış yaptın.',

    'verified' => 'E-posta adresin doğrulandı.',
    'verified_login' => 'E-posta adresin doğrulandı. Şimdi giriş yapabilirsin.',
    'verification_sent' => 'Yeni doğrulama bağlantısı e-posta adresine gönderildi.',

    'username_format' => 'Kullanıcı adı yalnızca harf, rakam, nokta ve alt çizgi içerebilir.',
    'username_reserved' => 'Bu kullanıcı adı kullanılamaz.',
    'terms_required' => 'Devam etmek için kullanım koşullarını kabul etmelisin.',
    'kvkk_required' => 'Devam etmek için KVKK aydınlatma metnini okuduğunu onaylamalısın.',
    'password_contains_identity' => 'Parola kullanıcı adını veya e-posta adresini içeremez.',

    'attributes' => [
        'username' => 'kullanıcı adı',
        'email' => 'e-posta',
        'password' => 'parola',
        'login' => 'kullanıcı adı veya e-posta',
        'terms' => 'kullanım koşulları',
        'kvkk' => 'KVKK onayı',
    ],

    'ui' => [
        'login' => [
            'title' => 'Tekrar hoş geldin',
            'intro' => 'Oyuncu paneline giriş yap.',
            'login' => 'Kullanıcı adı veya e-posta',
            'password' => 'Parola',
            'remember' => 'Bu cihazda beni hatırla (30 gün)',
            'submit' => 'Giriş yap',
            'forgot' => 'Şifremi unuttum',
            'no_account' => 'Hesabın yok mu?',
            'register' => 'Kayıt ol',
        ],
        'register' => [
            'title' => 'Hesabını oluştur',
            'intro' => 'Web hesabın; destek, başvuru ve oyun hesabını bağlamak için kullanılır. Oyun hesabından ayrıdır.',
            'username' => 'Kullanıcı adı',
            'username_hint' => '3–20 karakter; harf, rakam, nokta ve alt çizgi.',
            'email' => 'E-posta',
            'password' => 'Parola',
            'password_hint' => 'En az 10 karakter. Kullanıcı adını içermesin; bilinen sızıntılardaki parolalar kabul edilmez.',
            'password_confirmation' => 'Parola tekrarı',
            'terms' => ':link okudum ve kabul ediyorum.',
            'terms_link' => 'Kullanım koşullarını',
            'kvkk' => ':link okudum.',
            'kvkk_link' => 'KVKK aydınlatma metnini',
            'submit' => 'Hesap oluştur',
            'have_account' => 'Zaten hesabın var mı?',
            'login' => 'Giriş yap',
            'closed_title' => 'Kayıtlar şu an kapalı',
            'closed_text' => 'Yeni hesap oluşturma geçici olarak durduruldu. Duyurular için haberleri takip et.',
        ],
        'check_email' => [
            'title' => 'E-postanı kontrol et',
            'text' => 'Hesabını etkinleştirmek için :email adresine bir bağlantı gönderdik. Bağlantı 60 dakika geçerlidir.',
            'text_generic' => 'Hesabını etkinleştirmek için e-posta adresine bir bağlantı gönderdik. Bağlantı 60 dakika geçerlidir.',
            'spam' => 'E-posta birkaç dakika içinde gelmezse gereksiz (spam) klasörünü kontrol et.',
            'login' => 'Giriş ekranına dön',
        ],
        'verify' => [
            'title' => 'E-posta adresini doğrula',
            'text' => 'Panelin tüm özelliklerini kullanabilmek için e-posta adresini doğrulaman gerekiyor. Bağlantıyı bulamadıysan yenisini gönderebiliriz.',
            'resend' => 'Doğrulama bağlantısını yeniden gönder',
        ],
        'forgot' => [
            'title' => 'Şifreni mi unuttun?',
            'intro' => 'Hesabına kayıtlı e-posta adresini yaz; sıfırlama bağlantısı gönderelim.',
            'email' => 'E-posta',
            'submit' => 'Sıfırlama bağlantısı gönder',
            'back' => 'Giriş ekranına dön',
        ],
        'reset' => [
            'title' => 'Yeni parola belirle',
            'submit' => 'Parolayı güncelle',
        ],
        'confirm' => [
            'title' => 'Parolanı onayla',
            'intro' => 'Bu hassas bir işlem. Devam etmeden önce parolanı tekrar gir.',
            'submit' => 'Onayla',
        ],
        'two_factor' => [
            'title' => 'İki adımlı doğrulama',
            'intro' => 'Kimlik doğrulama uygulamandaki 6 haneli kodu gir.',
            'code' => 'Doğrulama kodu',
            'recovery_intro' => 'Telefonuna erişemiyorsan kurtarma kodlarından birini kullan.',
            'recovery_code' => 'Kurtarma kodu',
            'use_recovery' => 'Kurtarma kodu kullan',
            'use_code' => 'Doğrulama kodu kullan',
            'submit' => 'Doğrula',
        ],
        'side' => [
            'eyebrow' => 'Oyuncu paneli',
            'title' => 'Hesabın, karakterlerin ve destek talepların tek yerde.',
            'points' => ['Oyun hesabını tek kullanımlık kodla güvenle bağla', 'Destek taleplerini ve başvurularını takip et', 'Duyuruları ve güvenlik bildirimlerini kaçırma'],
        ],
    ],
];
