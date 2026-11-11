<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * docs/DATABASE.md §3.8 user_security_events, §3.9 email_change_requests, §3.10 consents (append-only, §7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_security_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event', 48);
            $table->char('identifier_hash', 64)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('ip_masked', 64)->nullable();
            $table->string('user_agent_summary', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
            $table->index(['ip_hash', 'created_at']);
        });

        Schema::create('email_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('new_email', 254);
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->char('anon_id', 26)->nullable();
            $table->string('type', 32);
            $table->string('version', 32);
            $table->boolean('granted');
            $table->string('ip_masked', 64)->nullable();
            $table->dateTime('created_at');
            $table->index(['user_id', 'type', 'created_at']);
        });

        foreach (['UPDATE' => 'update', 'DELETE' => 'delete'] as $operation => $suffix) {
            DB::unprepared("CREATE TRIGGER trg_consents_no_{$suffix} BEFORE {$operation} ON consents FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'consents is append-only'");
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_consents_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_consents_no_delete');
        Schema::dropIfExists('consents');
        Schema::dropIfExists('email_change_requests');
        Schema::dropIfExists('user_security_events');
    }
};
