<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Gerçek zaman (Laravel Reverb) anahtarı — docs/ARCHITECTURE.md §9. Yalnızca `BROADCAST_CONNECTION=reverb` ve uygulama
 * anahtarı tanımlıyken açıktır; kapalıyken sunucu yayın yapmaz, istemci bağlanmaz (polling devam eder).
 * İstemciye yalnızca public anahtar + host/port/şema verilir; secret asla.
 */
final class Realtime
{
    public static function enabled(): bool
    {
        $key = config('broadcasting.connections.reverb.key');
        $host = config('broadcasting.connections.reverb.options.host');

        return config('broadcasting.default') === 'reverb' && is_string($key) && $key !== '' && is_string($host) && $host !== '';
    }

    /** @return array{key: string, host: string, port: int, scheme: string}|null */
    public static function clientConfig(): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        $scheme = config('broadcasting.connections.reverb.options.scheme') === 'http' ? 'http' : 'https';

        return [
            'key' => (string) config('broadcasting.connections.reverb.key'),
            'host' => (string) config('broadcasting.connections.reverb.options.host'),
            'port' => (int) config('broadcasting.connections.reverb.options.port', $scheme === 'https' ? 443 : 80),
            'scheme' => $scheme,
        ];
    }

    /** CSP `connect-src` girdisi (`wss://host:port`) veya kapalıyken null. */
    public static function connectSource(): ?string
    {
        $config = self::clientConfig();
        if ($config === null) {
            return null;
        }

        return ($config['scheme'] === 'https' ? 'wss' : 'ws').'://'.$config['host'].':'.$config['port'];
    }

    /**
     * Bildirim `via()` için: etkinse `broadcast` kanalı eklenir.
     *
     * @param  list<string>  $channels
     * @return list<string>
     */
    public static function withBroadcast(array $channels): array
    {
        if (self::enabled()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }
}
