<?php

declare(strict_types=1);

// Akademi sınavları (Faz 10) — docs/DATABASE.md §10.17, CMS §5.2.
return [
    'quiz' => [
        'eyebrow' => 'Akademi sınavı',
        'start' => 'Sınava gir',
        'submit' => 'Cevapları gönder',
        'topic_cta' => 'Bu konu kısa bir sınavla tamamlanır.',
        'back_to_topic' => 'Konuya dön',
        'pass_rule' => 'Geçme eşiği %:pass',
        'attempts_left' => 'Kalan deneme: :count',
        'attempts_unlimited' => 'Deneme sınırı yok',
        'locked' => 'Yeni deneme :time sonrasında açılır.',
        'no_attempts' => 'Deneme hakkın kalmadı. Konuyu tekrar okuyup bir yetkiliye başvurabilirsin.',
        'passed_banner' => 'Sınavı %:score ile geçtin; konu tamamlandı. Doğru cevaplar aşağıda işaretli.',
        'last_score' => 'Son deneme: %:score',
        'correct' => 'Doğru cevap',
        'your_answer' => 'Senin cevabın',
        'passed' => 'Tebrikler! %:score ile geçtin, konu tamamlandı.',
        'failed' => 'Puanın %:score (gerekli %:pass). Konuyu tekrar okuyup yeniden dene.',
        'question' => 'Soru :n',
        'errors' => [
            'already_passed' => 'Bu sınavı zaten geçtin.',
            'no_attempts' => 'Deneme hakkın kalmadı.',
            'cooldown' => 'Yeni deneme :time sonrasında açılır.',
            'unanswered' => 'Tüm soruları cevapla.',
            'quiz_required' => 'Bu konu sınavla tamamlanır.',
        ],
    ],
    'admin' => [
        'questions' => 'Sorular',
        'add_question' => 'Soru ekle',
        'question' => 'Soru',
        'options' => 'Seçenekler (2–6, tam bir doğru)',
        'option' => 'Seçenek :n',
        'correct' => 'Doğru seçenek',
        'empty' => 'Henüz soru yok. Sınavı aktif etmeden önce en az bir soru ekleyin.',
        'saved' => 'Soru eklendi.',
        'deleted' => 'Soru silindi.',
        'delete' => 'Sil',
        'errors' => [
            'option_count' => 'En az 2, en fazla 6 seçenek girin.',
            'correct' => 'Doğru seçenek dolu seçeneklerden biri olmalı.',
        ],
    ],
];
