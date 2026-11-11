<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\User;
use App\Models\UserSecurityEvent;
use App\Support\IpMasker;
use App\Support\UserAgentSummary;
use Illuminate\Http\Request;

/**
 * Güvenlik olayları (docs/DATABASE.md §3.8, docs/SRS.md FR-AUTH-009/010).
 * Ham IP ve ham tanımlayıcı saklanmaz: HMAC + maske (SEC-043).
 */
final class SecurityEventLogger
{
    /** @param array<string, mixed> $metadata */
    public function record(?User $user, string $event, Request $request, array $metadata = [], ?string $identifier = null): void
    {
        UserSecurityEvent::query()->create([
            'user_id' => $user?->id,
            'event' => $event,
            'identifier_hash' => $identifier !== null ? hash_hmac('sha256', mb_strtolower($identifier), (string) config('app.key')) : null,
            'ip_hash' => IpMasker::hash($request->ip()),
            'ip_masked' => IpMasker::mask($request->ip()),
            'user_agent_summary' => UserAgentSummary::from($request->userAgent()),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
