<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MtaServer;
use Illuminate\Database\Seeder;

/**
 * Birincil MTA sunucusu kaydı (docs/DATABASE.md §4.2). Adres ACP › MTA entegrasyonu (Faz 7) ekranından değiştirilir.
 * Heartbeat gelmediği sürece public sayfada dürüstçe "Çevrimdışı" görünür.
 */
final class ServerSeeder extends Seeder
{
    public function run(): void
    {
        MtaServer::query()->firstOrCreate(['slug' => 'main'], [
            'name' => 'Meridyen Roleplay',
            'connect_host' => '127.0.0.1',
            'connect_port' => 22003,
            'max_players' => 300,
            'game_mode' => 'Roleplay',
            'map_name' => 'San Andreas',
            'is_primary' => true,
            'is_active' => true,
        ]);
    }
}
