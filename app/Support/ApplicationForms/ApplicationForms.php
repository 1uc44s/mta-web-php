<?php

declare(strict_types=1);

namespace App\Support\ApplicationForms;

use InvalidArgumentException;

/**
 * Sabit başvuru form şemaları — docs/SRS.md FR-APP-002. MVP'de form oluşturucu yoktur; alanlar burada sürümlenir.
 * `application_types.form_key` bu kayıttaki anahtarlardan biri olmalıdır. Cevaplar `question_label` kopyasıyla saklanır,
 * böylece şema değişse de eski başvurular okunabilir kalır.
 *
 * @phpstan-type Field array{label: string, type: string, rules: list<string>, hint?: string, options?: array<string, string>}
 */
final class ApplicationForms
{
    /** @var array<string, array{name: string, fields: array<string, Field>}> */
    private const FORMS = [
        'faction_default' => [
            'name' => 'Fraksiyon başvurusu',
            'fields' => [
                'character_story' => ['label' => 'Karakterinin geçmişi ve bu fraksiyona katılma nedeni', 'type' => 'textarea', 'rules' => ['required', 'string', 'min:150', 'max:4000'], 'hint' => 'En az 150 karakter. Karakterin kim, nereden geliyor, neden burada?'],
                'roleplay_experience' => ['label' => 'Roleplay deneyimin', 'type' => 'textarea', 'rules' => ['required', 'string', 'min:50', 'max:2000']],
                'weekly_hours' => ['label' => 'Haftada ortalama kaç saat aktif olabilirsin?', 'type' => 'number', 'rules' => ['required', 'integer', 'min:1', 'max:80']],
                'scenario' => ['label' => 'Senaryo: Görev sırasında kuralları çiğneyen bir ekip arkadaşına nasıl yaklaşırsın?', 'type' => 'textarea', 'rules' => ['required', 'string', 'min:100', 'max:2000']],
                'discord' => ['label' => 'Discord kullanıcı adın (isteğe bağlı)', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:64']],
            ],
        ],
        'staff_v1' => [
            'name' => 'Yetkili başvurusu',
            'fields' => [
                'age' => ['label' => 'Yaşın', 'type' => 'number', 'rules' => ['required', 'integer', 'min:16', 'max:99']],
                'motivation' => ['label' => 'Neden ekibe katılmak istiyorsun?', 'type' => 'textarea', 'rules' => ['required', 'string', 'min:150', 'max:3000']],
                'experience' => ['label' => 'Daha önceki yetkili / moderasyon deneyimlerin', 'type' => 'textarea', 'rules' => ['required', 'string', 'min:50', 'max:3000']],
                'preferred_area' => ['label' => 'Hangi alanda görev almak istersin?', 'type' => 'select', 'rules' => ['required', 'string'], 'options' => ['support' => 'Destek', 'moderation' => 'Moderasyon', 'events' => 'Etkinlik', 'content' => 'İçerik']],
                'weekly_hours' => ['label' => 'Haftada ortalama kaç saat ayırabilirsin?', 'type' => 'number', 'rules' => ['required', 'integer', 'min:1', 'max:80']],
                'rule_case' => ['label' => 'Senaryo: Bir oyuncu başka bir oyuncunun onu sebepsiz öldürdüğünü iddia ediyor. Adım adım ne yaparsın?', 'type' => 'textarea', 'rules' => ['required', 'string', 'min:100', 'max:2000']],
            ],
        ],
        'whitelist_v1' => [
            'name' => 'Whitelist başvurusu',
            'fields' => [
                'character_concept' => ['label' => 'Karakter konseptin', 'type' => 'textarea', 'rules' => ['required', 'string', 'min:100', 'max:3000']],
                'rules_read' => ['label' => 'Kuralları okudun mu? En önemli bulduğun iki kuralı kısaca yaz.', 'type' => 'textarea', 'rules' => ['required', 'string', 'min:30', 'max:1000']],
                'reference' => ['label' => 'Seni tanıyan bir oyuncu (isteğe bağlı)', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:64']],
            ],
        ],
    ];

    /** @return array<string, string> form_key => ad */
    public static function options(): array
    {
        return array_map(fn (array $form): string => $form['name'], self::FORMS);
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::FORMS);
    }

    /** @return array<string, Field> */
    public static function fields(string $key): array
    {
        if (! self::exists($key)) {
            throw new InvalidArgumentException("Bilinmeyen başvuru formu: {$key}");
        }

        return self::FORMS[$key]['fields'];
    }

    /**
     * Laravel doğrulama kuralları. Taslakta alanlar isteğe bağlıdır (yalnızca tip/üst sınır), gönderimde tam kurallar.
     *
     * @return array<string, list<string>>
     */
    public static function rules(string $key, bool $draft): array
    {
        $rules = [];
        foreach (self::fields($key) as $field => $def) {
            $fieldRules = $draft
                ? ['nullable', ...array_values(array_filter($def['rules'], fn (string $r): bool => ! in_array($r, ['required', 'nullable'], true) && ! str_starts_with($r, 'min:')))]
                : $def['rules'];

            if ($def['type'] === 'select') {
                $fieldRules[] = 'in:'.implode(',', array_keys($def['options'] ?? []));
            }

            $rules['answers.'.$field] = $fieldRules;
        }

        return $rules;
    }

    /** @return array<string, string> answers.key => etiket (doğrulama mesajları için) */
    public static function attributes(string $key): array
    {
        $out = [];
        foreach (self::fields($key) as $field => $def) {
            $out['answers.'.$field] = $def['label'];
        }

        return $out;
    }
}
