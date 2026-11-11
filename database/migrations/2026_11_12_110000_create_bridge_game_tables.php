<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 7 — MTA köprüsü, oyun hesapları ve karakterler. docs/DATABASE.md §4.3–4.6, §5.1–5.7.
 * `game_commands` tablosu burada oluşturulur (bağlantı iptalinde `account.link_revoked` kuyruğa girer);
 * pending/result uçları Faz 8'de.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mta_server_id')->constrained()->cascadeOnDelete();
            $table->string('key_id', 32)->unique('uq_ic_key_id');
            $table->text('secret');
            $table->json('scopes');
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->index(['mta_server_id', 'revoked_at'], 'idx_ic_server_revoked');
        });

        Schema::create('bridge_nonces', function (Blueprint $table): void {
            $table->char('nonce', 64)->primary();
            $table->foreignId('credential_id')->constrained('integration_credentials')->cascadeOnDelete();
            $table->dateTime('created_at')->index();
        });

        Schema::create('server_heartbeats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mta_server_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('player_count');
            $table->unsignedSmallInteger('max_players');
            $table->json('payload')->nullable();
            $table->dateTime('received_at');
            $table->index(['mta_server_id', 'received_at'], 'idx_sh_server_received');
        });

        Schema::create('server_stats_daily', function (Blueprint $table): void {
            $table->foreignId('mta_server_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('peak_players')->default(0);
            $table->decimal('avg_players', 6, 2)->default(0);
            $table->decimal('uptime_ratio', 5, 4)->default(0);
            $table->unsignedInteger('unique_accounts')->default(0);
            $table->primary(['mta_server_id', 'date']);
        });

        Schema::create('mta_accounts', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_mta_accounts_ulid');
            $table->foreignId('mta_server_id')->constrained()->restrictOnDelete();
            $table->string('mta_account_ref', 64);
            $table->string('account_name', 64)->index('idx_mta_accounts_name');
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['mta_server_id', 'mta_account_ref'], 'uq_mta_accounts_ref');
        });

        Schema::create('game_account_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('mta_account_id')->constrained()->restrictOnDelete();
            $table->string('status', 32)->default('active');
            $table->string('verification_method', 32);
            $table->dateTime('linked_at');
            $table->dateTime('unlink_requested_at')->nullable();
            $table->string('unlink_reason', 500)->nullable();
            $table->dateTime('unlinked_at')->nullable();
            $table->foreignId('unlinked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['user_id', 'status'], 'idx_gal_user_status');
        });
        // DATABASE §5.2: bir oyun hesabının aynı anda tek aktif bağlantısı (MariaDB'de partial index yerine generated column).
        DB::statement("ALTER TABLE game_account_links ADD COLUMN active_mta_account_id BIGINT UNSIGNED AS (IF(status IN ('active','unlink_requested'), mta_account_id, NULL)) STORED AFTER verification_method");
        DB::statement('ALTER TABLE game_account_links ADD UNIQUE INDEX uq_gal_active_account (active_mta_account_id)');

        Schema::create('game_link_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 32)->default('account_link');
            $table->char('code_hash', 64)->unique('uq_glc_code_hash');
            $table->foreignId('mta_server_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('target_mta_account_id')->nullable()->constrained('mta_accounts')->cascadeOnDelete();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('expires_at')->index('idx_glc_expires');
            $table->dateTime('used_at')->nullable();
            $table->foreignId('used_by_mta_account_id')->nullable()->constrained('mta_accounts')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index(['user_id', 'purpose', 'used_at', 'cancelled_at'], 'idx_glc_user_purpose');
        });

        Schema::create('characters', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_characters_ulid');
            $table->foreignId('mta_server_id')->constrained()->restrictOnDelete();
            $table->foreignId('mta_account_id')->constrained()->restrictOnDelete();
            $table->string('mta_character_ref', 64);
            $table->string('name', 64)->index('idx_characters_name');
            $table->string('status', 32)->default('active');
            $table->foreignId('faction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_job_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('level')->nullable();
            $table->unsignedInteger('playtime_minutes')->nullable();
            $table->json('public_profile')->nullable();
            $table->boolean('is_profile_hidden')->default(false);
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('created_in_game_at')->nullable();
            $table->dateTime('synced_at');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['mta_server_id', 'mta_character_ref'], 'uq_characters_ref');
            $table->index('mta_account_id', 'idx_characters_account');
        });

        Schema::create('game_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mta_server_id')->constrained()->cascadeOnDelete();
            $table->char('event_id', 36);
            $table->string('type', 48);
            $table->foreignId('mta_account_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->string('status', 16)->default('received');
            $table->string('error', 500)->nullable();
            $table->dateTime('occurred_at');
            $table->dateTime('received_at');
            $table->dateTime('processed_at')->nullable();
            $table->unique(['mta_server_id', 'event_id'], 'uq_game_events');
            $table->index(['type', 'received_at'], 'idx_ge_type_received');
            $table->index(['mta_account_id', 'occurred_at'], 'idx_ge_account');
        });

        Schema::create('game_commands', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_gc_ulid');
            $table->foreignId('mta_server_id')->constrained()->restrictOnDelete();
            $table->string('type', 32);
            $table->foreignId('target_mta_account_id')->nullable()->constrained('mta_accounts')->restrictOnDelete();
            $table->json('payload');
            $table->string('idempotency_key', 100)->unique('uq_gc_idempotency');
            $table->string('status', 16)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->json('result')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['mta_server_id', 'status', 'created_at'], 'idx_gc_server_status');
        });

        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->foreign('character_id')->references('id')->on('characters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropForeign(['character_id']);
        });

        foreach (['game_commands', 'game_events', 'characters', 'game_link_codes', 'game_account_links', 'mta_accounts', 'server_stats_daily', 'server_heartbeats', 'bridge_nonces', 'integration_credentials'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
