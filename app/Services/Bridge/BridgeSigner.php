<?php

declare(strict_types=1);

namespace App\Services\Bridge;

/**
 * Köprü imza protokolü — docs/ARCHITECTURE.md §7.2, SRS FR-BRG-001/002.
 *
 *   canonical = METHOD \n PATH \n TIMESTAMP \n NONCE \n hex(sha256(BODY))
 *   signature = hex(hmac_sha256(secret, canonical))
 *
 * PATH sorgu dizesi hariç, decode edilmemiş istek yoludur. GET'te BODY boş string'dir.
 * Lua tarafı (`mta-resource/web_bridge/server/signature.lua`) aynı algoritmayı ve aynı test vektörlerini kullanır.
 */
final class BridgeSigner
{
    public static function canonical(string $method, string $path, string $timestamp, string $nonce, string $body): string
    {
        return strtoupper($method)."\n".$path."\n".$timestamp."\n".$nonce."\n".hash('sha256', $body);
    }

    public static function sign(string $secret, string $canonical): string
    {
        return hash_hmac('sha256', $canonical, $secret);
    }

    public static function matches(string $secret, string $canonical, string $signature): bool
    {
        return hash_equals(self::sign($secret, $canonical), strtolower($signature));
    }
}
