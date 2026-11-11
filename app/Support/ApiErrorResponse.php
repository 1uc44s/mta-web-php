<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * JSON uçları için tek hata biçimi (docs/SRS.md §8).
 * Stack trace, SQL, dosya yolu veya exception mesajı istemciye asla gönderilmez.
 */
final class ApiErrorResponse
{
    /** @var array<int, string> */
    private const CODES_BY_STATUS = [
        400 => 'BAD_REQUEST',
        401 => 'UNAUTHENTICATED',
        403 => 'FORBIDDEN',
        404 => 'NOT_FOUND',
        405 => 'METHOD_NOT_ALLOWED',
        409 => 'CONFLICT',
        413 => 'PAYLOAD_TOO_LARGE',
        419 => 'SESSION_EXPIRED',
        422 => 'VALIDATION_ERROR',
        429 => 'RATE_LIMITED',
        503 => 'SERVICE_UNAVAILABLE',
    ];

    public static function fromThrowable(Throwable $e): JsonResponse
    {
        $status = self::statusFor($e);
        $code = self::CODES_BY_STATUS[$status] ?? 'SERVER_ERROR';

        $error = [
            'code' => $code,
            'message' => __('errors.api.'.$code),
        ];

        if ($e instanceof ValidationException) {
            $error['details'] = collect($e->errors())
                ->flatMap(fn (array $messages, string $field): array => array_map(
                    fn (string $message): array => ['field' => $field, 'message' => $message],
                    $messages,
                ))
                ->values()
                ->all();
        }

        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

        return new JsonResponse([
            'success' => false,
            'error' => $error,
            'requestId' => RequestId::current(),
        ], $status, $headers);
    }

    private static function statusFor(Throwable $e): int
    {
        return match (true) {
            $e instanceof ValidationException => 422,
            $e instanceof AuthenticationException => 401,
            $e instanceof AuthorizationException => 403,
            $e instanceof ModelNotFoundException => 404,
            $e instanceof TokenMismatchException => 419,
            $e instanceof ThrottleRequestsException => 429,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };
    }
}
