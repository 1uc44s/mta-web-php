<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * docs/DATABASE.md §12 seed sırası. DemoSeeder yalnızca yerel ortamda çalışır.
 * İlk süper admin seed ile oluşturulmaz: `php artisan mrp:make-super-admin {email}` (Faz 4).
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ServerSeeder::class,
            ContentSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->call(DemoSeeder::class);
        }
    }
}
