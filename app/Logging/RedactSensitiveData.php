<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;

/**
 * Log bağlamındaki hassas anahtarları maskeler (docs/ARCHITECTURE.md §13, docs/SRS.md SEC-043).
 * `config/logging.php` › `tap` sınıfıdır: kanala processor ekler (stack kanalı alt kanalların processor'larını devralır).
 * Anahtar adının PARÇASI eşleşir (`password_confirmation`, `X-Bridge-Signature`, `remember_token`);
 * `code` yalnızca tam ad veya bilinen OTP adlarıyla eşleşir (status_code maskelenmez).
 */
final class RedactSensitiveData
{
    public const MASK = '[REDACTED]';

    private const CONTAINS = ['password', 'token', 'secret', 'signature', 'serial', 'authorization', 'cookie', 'api_key', 'apikey'];

    private const EXACT = ['code', 'recovery_code', 'one_time_code', 'otp', 'link_code'];

    private const MAX_DEPTH = 6;

    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if ($monolog instanceof MonologLogger) {
            $monolog->pushProcessor(static fn (LogRecord $record): LogRecord => $record->with(
                context: self::redact($record->context),
                extra: self::redact($record->extra),
            ));
        }
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function redact(array $data, int $depth = 0): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && self::isSensitive($key)) {
                $data[$key] = self::MASK;
            } elseif (is_array($value) && $depth < self::MAX_DEPTH) {
                $data[$key] = self::redact($value, $depth + 1);
            }
        }

        return $data;
    }

    private static function isSensitive(string $key): bool
    {
        $key = strtolower(str_replace('-', '_', $key));

        if (in_array($key, self::EXACT, true)) {
            return true;
        }

        foreach (self::CONTAINS as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }
}
