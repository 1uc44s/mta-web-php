<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Services\Settings\SettingsRepository;

/**
 * Discord webhook kanalı — docs/SRS.md FR-NTF-002 (R2). Webhook URL'leri yalnızca `.env`'de (secret), açma/kapama
 * `discord.*` ayarlarında. Metin Discord markdown'ı ve toplu bahsetmelerden arındırılır; `allowed_mentions` boş.
 */
final class DiscordWebhook
{
    public const MAX_LENGTH = 2000;

    public function __construct(private readonly SettingsRepository $settings) {}

    /** Hedef açık ve URL geçerli mi? Duyurular `discord.announcements`, ops `discord.ops_alerts` ayarına bağlıdır. */
    public function enabled(string $target): bool
    {
        $setting = $target === 'ops' ? 'discord.ops_alerts' : 'discord.announcements';

        return $this->settings->bool($setting) && $this->url($target) !== null;
    }

    /** Yalnızca `https://discord.com/api/webhooks/…` (veya discordapp.com) kabul edilir — yanlış yapılandırma SSRF'e dönüşmez. */
    public function url(string $target): ?string
    {
        $webhooks = (array) config('roleplay.discord.webhooks', []);
        $url = $webhooks[$target] ?? null;

        if (! is_string($url) || preg_match('#^https://(?:canary\.|ptb\.)?discord(?:app)?\.com/api/webhooks/\d+/[\w-]+$#', $url) !== 1) {
            return null;
        }

        return $url;
    }

    /** Kullanıcı/yetkili kaynaklı metni güvenli düz metne çevirir. */
    public static function sanitize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = (string) preg_replace('/([\\\\*_~`>|])/u', '\\\\$1', $text);
        // @everyone / @here / <@…> bahsetmeleri sıfır genişlikli boşlukla kırılır (allowed_mentions'a ek savunma).
        $text = (string) preg_replace('/@(everyone|here)/iu', "@\u{200B}$1", $text);

        return str_replace('<@', "<\u{200B}@", $text);
    }

    /** @return array{content: string, allowed_mentions: array{parse: list<string>}} */
    public static function payload(string $content): array
    {
        if (mb_strlen($content) > self::MAX_LENGTH) {
            $content = mb_substr($content, 0, self::MAX_LENGTH - 1).'…';
        }

        return ['content' => $content, 'allowed_mentions' => ['parse' => []]];
    }
}
