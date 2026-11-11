<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\AuditAction;
use App\Logging\RedactSensitiveData;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\IpMasker;
use App\Support\RequestId;
use App\Support\UserAgentSummary;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Append-only, hash zincirli denetim kaydı — docs/DATABASE.md §11.3, docs/ADMIN.md §8.
 * hash = SHA256(prev_hash ‖ canonical_json(satır)). Yazımlar GET_LOCK('audit_chain') ile serileştirilir.
 */
final class AuditLogger
{
    private const LOCK = 'audit_chain';

    private const FIELDS = ['ulid', 'actor_type', 'actor_id', 'action', 'target_type', 'target_id', 'severity', 'changes', 'reason', 'ip_masked', 'user_agent_summary', 'request_id', 'created_at'];

    /** `changes` içinde her zaman maskelenen ek anahtar parçaları (ADMIN §8). */
    private const EXTRA_REDACT = ['two_factor', 'address'];

    /** @param  array<string, mixed>  $changes  ['before' => [...], 'after' => [...]] */
    public function record(
        AuditAction $action,
        ?Model $target = null,
        array $changes = [],
        ?string $reason = null,
        ?User $actor = null,
        string $actorType = 'user',
    ): AuditLog {
        $reason = $reason !== null ? trim($reason) : null;

        if ($action->requiresReason() && ($reason === null || $reason === '')) {
            throw new InvalidArgumentException("{$action->value} için gerekçe zorunludur.");
        }

        $authUser = auth()->user();
        $actor ??= $authUser instanceof User ? $authUser : null;
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        $row = [
            'ulid' => strtolower((string) Str::ulid()),
            'actor_type' => $actor === null && $actorType === 'user' ? 'system' : $actorType,
            'actor_id' => $actor?->id,
            'action' => $action->value,
            'target_type' => $target !== null ? Str::snake(class_basename($target)) : null,
            'target_id' => $target?->getKey() !== null ? (int) $target->getKey() : null,
            'severity' => $action->severity(),
            'changes' => $changes === [] ? null : self::redact($changes),
            'reason' => $reason !== null ? mb_substr($reason, 0, 1000) : null,
            'ip_masked' => $request !== null ? IpMasker::mask($request->ip()) : null,
            'user_agent_summary' => $request !== null ? UserAgentSummary::from($request->userAgent()) : null,
            'request_id' => RequestId::current(),
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];

        return $this->withLock(function () use ($row): AuditLog {
            $prev = DB::table('audit_logs')->orderByDesc('id')->value('hash');
            $row['prev_hash'] = $prev !== null ? (string) $prev : null;
            $row['hash'] = self::hash($row['prev_hash'], $row);

            $log = new AuditLog;
            $log->forceFill($row)->save();

            return $log;
        });
    }

    /** Zinciri baştan doğrular. Bozuk ilk kaydın id'sini, zincir sağlamsa null döner. */
    public function verify(): ?int
    {
        $prev = null;
        $broken = null;

        DB::table('audit_logs')->orderBy('id')->chunkById(500, function ($rows) use (&$prev, &$broken): bool {
            foreach ($rows as $r) {
                $row = (array) $r;
                $row['changes'] = $row['changes'] !== null ? json_decode((string) $row['changes'], true) : null;

                if ($row['prev_hash'] !== $prev || ! hash_equals((string) $row['hash'], self::hash($prev, $row))) {
                    $broken = (int) $row['id'];

                    return false;
                }

                $prev = (string) $row['hash'];
            }

            return true;
        });

        return $broken;
    }

    /** @param  array<string, mixed>  $row */
    public static function hash(?string $prevHash, array $row): string
    {
        $data = [];
        foreach (self::FIELDS as $f) {
            $value = $row[$f] ?? null;
            $data[$f] = match (true) {
                $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
                in_array($f, ['actor_id', 'target_id'], true) && $value !== null => (int) $value,
                default => $value,
            };
        }

        return hash('sha256', ($prevHash ?? '').self::canonicalJson($data));
    }

    private static function canonicalJson(mixed $value): string
    {
        $sort = static function (mixed $v) use (&$sort): mixed {
            if (! is_array($v)) {
                return $v;
            }
            if (! array_is_list($v)) {
                ksort($v);
            }

            return array_map($sort, $v);
        };

        return (string) json_encode($sort($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private static function redact(array $data): array
    {
        $data = RedactSensitiveData::redact($data);

        foreach ($data as $key => $value) {
            $sensitive = is_string($key) && array_filter(self::EXTRA_REDACT, fn (string $n): bool => str_contains(strtolower($key), $n)) !== [];

            if ($sensitive) {
                $data[$key] = RedactSensitiveData::MASK;
            } elseif (is_array($value)) {
                $data[$key] = self::redact($value);
            }
        }

        return $data;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withLock(callable $callback): mixed
    {
        if ((int) DB::scalar('SELECT GET_LOCK(?, 5)', [self::LOCK]) !== 1) {
            throw new RuntimeException('audit_chain kilidi alınamadı.');
        }

        try {
            return $callback();
        } finally {
            DB::scalar('SELECT RELEASE_LOCK(?)', [self::LOCK]);
        }
    }
}
