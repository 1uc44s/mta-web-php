<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\Bridge\ServerStatusResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/v1/public/server-status — docs/ROUTES.md §7.1. 15 sn cache + ETag. */
final class ServerStatusController extends Controller
{
    public function __invoke(Request $request, ServerStatusResolver $resolver): JsonResponse
    {
        $snapshot = $resolver->primary();

        $response = response()->json(['success' => true, 'data' => $snapshot?->toArray()])
            ->setPublic()
            ->setMaxAge((int) config('roleplay.server_status.public_cache_seconds'));

        $response->setEtag(hash('xxh128', (string) $response->getContent()));
        $response->isNotModified($request);

        return $response;
    }
}
