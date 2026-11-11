<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\RequestId;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Her isteğe bir korelasyon kimliği atar ve yanıta ekler (docs/SRS.md NFR-OBS-001).
 * Geçerli biçimde gelen X-Request-Id taşınır; aksi halde yeni ULID üretilir.
 */
final class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = RequestId::assign($request->headers->get(RequestId::HEADER));

        $response = $next($request);
        $response->headers->set(RequestId::HEADER, $id);

        return $response;
    }
}
