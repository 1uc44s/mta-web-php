<?php

declare(strict_types=1);

// Hata metinleri — yazım kılavuzu: docs/DESIGN.md §9 (ne oldu + ne yapabilirsin).

return [
    'api' => [
        'BAD_REQUEST' => 'İstek anlaşılamadı.',
        'UNAUTHENTICATED' => 'Bu işlem için giriş yapman gerekiyor.',
        'FORBIDDEN' => 'Bu işlem için yetkin yok.',
        'NOT_FOUND' => 'Aradığın kayıt bulunamadı.',
        'METHOD_NOT_ALLOWED' => 'Bu istek yöntemi desteklenmiyor.',
        'CONFLICT' => 'İstek mevcut durumla çakışıyor.',
        'PAYLOAD_TOO_LARGE' => 'Gönderilen veri çok büyük.',
        'SESSION_EXPIRED' => 'Oturumun zaman aşımına uğradı. Sayfayı yenileyip tekrar dene.',
        'VALIDATION_ERROR' => 'Gönderilen bilgiler kontrol edilmeli.',
        'RATE_LIMITED' => 'Çok fazla istek gönderdin. Biraz bekleyip tekrar dene.',
        'SERVICE_UNAVAILABLE' => 'Hizmet şu anda bakımda. Kısa süre sonra tekrar dene.',
        'SERVER_ERROR' => 'Beklenmeyen bir hata oluştu. Sorun devam ederse istek kimliğiyle destek talebi aç.',
    ],

    'pages' => [
        'home' => 'Ana sayfaya dön',
        'back' => 'Geri dön',
        'reload' => 'Sayfayı yenile',
        'request_id' => 'İstek kimliği',

        '403' => [
            'title' => 'Bu alana erişimin yok',
            'text' => 'Bu sayfayı görüntülemek için gereken yetkiye sahip değilsin. Bir hata olduğunu düşünüyorsan destek ekibine yaz.',
        ],
        '404' => [
            'title' => 'Bu koordinatta bir şey yok',
            'text' => 'Aradığın sayfa taşınmış ya da hiç var olmamış olabilir. Ana sayfadan yolunu yeniden bulabilirsin.',
        ],
        '419' => [
            'title' => 'Oturumun zaman aşımına uğradı',
            'text' => 'Güvenliğin için form süresi doldu. Sayfayı yenileyip tekrar gönderebilirsin.',
        ],
        '429' => [
            'title' => 'Biraz yavaşla',
            'text' => 'Kısa sürede çok fazla istek gönderdin. Birkaç dakika bekleyip tekrar dene.',
        ],
        '500' => [
            'title' => 'Bir şeyler ters gitti',
            'text' => 'Beklenmeyen bir hata oluştu ve kaydedildi. Sorun devam ederse aşağıdaki istek kimliğiyle destek talebi açabilirsin.',
        ],
        '503' => [
            'title' => 'Kısa bir bakımdayız',
            'text' => 'Sistemi iyileştiriyoruz. Kısa süre sonra tekrar burada olacağız.',
        ],
    ],
];
