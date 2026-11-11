<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * docs/DATABASE.md §4.1 system_settings, §4.2 mta_servers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->string('group', 50)->index();
            $table->json('value')->nullable();
            $table->string('type', 16)->default('string');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_secret')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('mta_servers', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 100);
            $table->string('connect_host', 255);
            $table->unsignedSmallInteger('connect_port')->default(22003);
            $table->unsignedSmallInteger('max_players')->nullable();
            $table->string('game_mode', 100)->nullable();
            $table->string('map_name', 100)->nullable();
            $table->dateTime('last_heartbeat_at')->nullable();
            $table->unsignedSmallInteger('last_player_count')->nullable();
            $table->string('resource_version', 32)->nullable();
            $table->boolean('is_maintenance')->default(false);
            $table->string('maintenance_note', 255)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->datetimes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mta_servers');
        Schema::dropIfExists('system_settings');
    }
};
