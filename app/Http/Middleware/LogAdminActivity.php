<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminActivityLog;
use App\Models\User;
use App\Support\IpMasker;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * docs/DATABASE.md §11.4: admin görüntüleme ve işlemlerinin izi (rota, yöntem, hedef kaynak). Yanıttan sonra yazılır;
 * yalnızca başarılı (< 400) istekler kaydedilir.
 */
final class LogAdminActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $route = $request->route();

        if ($user instanceof User && $route !== null && $response->getStatusCode() < 400) {
            $target = collect($route->parameters())->first(fn (mixed $p): bool => $p instanceof Model);

            AdminActivityLog::query()->create([
                'user_id' => $user->id,
                'route_name' => Str::limit((string) ($route->getName() ?? $request->path()), 100, ''),
                'method' => $request->method(),
                'target_type' => $target instanceof Model ? Str::snake(class_basename($target)) : null,
                'target_id' => $target instanceof Model ? (int) $target->getKey() : null,
                'ip_masked' => IpMasker::mask($request->ip()),
            ]);
        }

        return $response;
    }
}
