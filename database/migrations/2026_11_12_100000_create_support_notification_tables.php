<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 6 — destek sistemi, bildirimler ve bildirim tercihleri. docs/DATABASE.md §3.11, §8.
 * `support_tickets.character_id` için FK, `characters` tablosuyla birlikte Faz 7'de eklenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('default_priority', 16)->default('normal');
            $table->boolean('requires_linked_account')->default(false);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        // Tek satırlık sayaç: UPDATE … SET n = LAST_INSERT_ID(n + 1) ile yarışsız numara (DATABASE §8.2).
        Schema::create('support_ticket_sequence', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedInteger('n')->default(0);
        });
        DB::table('support_ticket_sequence')->insert(['id' => 1, 'n' => 0]);

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_st_ulid');
            $table->string('number', 16)->unique('uq_st_number');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('support_categories')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('character_id')->nullable();
            $table->string('subject', 120);
            $table->string('status', 24)->default('open');
            $table->string('priority', 16)->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->char('idempotency_key', 36);
            $table->dateTime('last_message_at')->nullable();
            $table->dateTime('last_player_message_at')->nullable();
            $table->dateTime('last_staff_message_at')->nullable();
            $table->dateTime('first_staff_response_at')->nullable();
            $table->boolean('player_unread')->default(false);
            $table->boolean('staff_unread')->default(true);
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reopen_requested_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['user_id', 'idempotency_key'], 'uq_st_user_idempotency');
            $table->index(['branch_id', 'status', 'priority', 'last_message_at'], 'idx_st_queue');
            $table->index(['user_id', 'status', 'updated_at'], 'idx_st_user');
            $table->index(['assigned_to', 'status'], 'idx_st_assigned');
        });

        Schema::create('support_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('author_type', 16);
            $table->string('visibility', 16)->default('public');
            $table->text('body');
            $table->dateTime('edited_at')->nullable();
            $table->dateTime('created_at');
            $table->index(['ticket_id', 'created_at'], 'idx_sm_ticket_created');
            $table->index(['ticket_id', 'visibility'], 'idx_sm_ticket_visibility');
        });

        Schema::create('support_attachments', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('support_messages')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('scan_status', 16)->default('skipped');
            $table->dateTime('created_at');
        });

        Schema::create('support_ticket_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32);
            $table->string('from_value', 64)->nullable();
            $table->string('to_value', 64)->nullable();
            $table->string('note', 500)->nullable();
            $table->dateTime('created_at');
            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('support_macros', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 100);
            $table->text('body');
            $table->foreignId('category_id')->nullable()->constrained('support_categories')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('user_notification_preferences', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('channel', 32);
            $table->boolean('enabled')->default(true);
            $table->dateTime('updated_at')->nullable();
            $table->primary(['user_id', 'category', 'channel']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->dateTime('read_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'user_notification_preferences', 'support_macros', 'support_ticket_events', 'support_attachments', 'support_messages', 'support_tickets', 'support_ticket_sequence', 'support_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
