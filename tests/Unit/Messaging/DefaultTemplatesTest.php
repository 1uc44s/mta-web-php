<?php

declare(strict_types=1);

use App\Services\Messaging\DefaultTemplates;

it('bağlam eksik veya fazla değişken içerirse reddeder', function (array $context): void {
    DefaultTemplates::render('auth.already_registered', $context);
})->throws(InvalidArgumentException::class)->with([
    'eksik' => [['link.login' => 'https://x.test/login']],
    'fazla' => [['link.login' => 'a', 'link.reset' => 'b', 'user.email' => 'c']],
]);

it('bilinmeyen anahtarı reddeder', function (): void {
    DefaultTemplates::render('yok.boyle', []);
})->throws(InvalidArgumentException::class);

it('her varsayılan şablon kendi değişken listesiyle render edilir ve yer tutucu bırakmaz', function (): void {
    $templates = array_filter(trans('messages'), 'is_array');
    expect($templates)->not->toBeEmpty();

    foreach ($templates as $key => $template) {
        $context = array_combine($template['variables'], array_map(fn (string $v): string => "[{$v}]", $template['variables']));
        $out = DefaultTemplates::render($key, $context);

        $text = implode("\n", [$out['subject'], $out['greeting'], ...$out['lines'], ...$out['outro']]);
        expect($text)->not->toContain('{{')
            ->and(mb_strlen($out['subject']))->toBeLessThanOrEqual(200);
    }
});
