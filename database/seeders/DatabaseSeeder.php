<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * docs/DATABASE.md §12 seed sırası. DemoSeeder yalnızca yerel ortamda çalışır.
 * Production'da ilk süper admin seed ile oluşturulmaz: `php artisan mrp:make-super-admin {email}` (Faz 4).
 * Yerelde (APP_ENV=local) DefaultAdminSeeder varsayılan süper admini oluşturur (docs/ADMIN.md §4.4).
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ServerSeeder::class,
            AccessControlSeeder::class,
            MessageTemplateSeeder::class,
            ContentSeeder::class,
            EconomySeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->call([DemoSeeder::class, DefaultAdminSeeder::class]);
        }
    }
}
