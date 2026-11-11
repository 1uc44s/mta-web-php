<?php

declare(strict_types=1);

namespace App\Services\System;

use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Sistem sağlığı okumaları — docs/ROUTES.md §6.1. Secret değer döndürmez; yalnızca sürüm, sürücü adı ve sayılar.
 */
final class HealthProbe
{
    /** @return array<string, string> */
    public function info(): array
    {
        return [
            'environment' => (string) app()->environment(),
            'php' => PHP_VERSION,
            'laravel' => Application::VERSION,
            'database' => (string) DB::scalar('SELECT VERSION()'),
            'mail' => (string) config('mail.default'),
            'queue_driver' => (string) config('queue.default'),
        ];
    }

    public function pendingJobs(): int
    {
        return DB::table('jobs')->count();
    }

    /** @return Collection<int, \stdClass> */
    public function failedJobs(int $limit = 20): Collection
    {
        return DB::table('failed_jobs')->latest('id')->limit($limit)->get(['uuid', 'queue', 'failed_at', 'exception']);
    }

    public function retry(string $uuid): bool
    {
        if (! DB::table('failed_jobs')->where('uuid', $uuid)->exists()) {
            return false;
        }

        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return true;
    }
}
