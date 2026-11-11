<?php

declare(strict_types=1);

// Parola sıfırlama aracısı mesajları. "user" mesajı da genel tutulur: hesap varlığı ifşa edilmez (SEC-005).

return [
    'reset' => 'Parolan güncellendi. Diğer cihazlardaki oturumların kapatıldı; yeni parolanla giriş yapabilirsin.',
    'sent' => 'Bu e-posta adresi kayıtlıysa sıfırlama bağlantısı gönderildi.',
    'throttled' => 'Lütfen yeniden denemeden önce biraz bekle.',
    'token' => 'Bu sıfırlama bağlantısı geçersiz veya süresi dolmuş. Yeni bir bağlantı iste.',
    'user' => 'Bu e-posta adresi kayıtlıysa sıfırlama bağlantısı gönderildi.',
];
