<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 8 — başvurular, cezalar, itirazlar, cihazlar. docs/DATABASE.md §5.5, §9, §11.1–11.2.
 * `factions.application_type_id` ve `game_jobs.application_type_id` FK'leri de burada eklenir (Faz 2 notu).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_types', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique('uq_application_types_slug');
            $table->string('name', 100);
            $table->string('kind', 32);
            $table->string('form_key', 64);
            $table->text('description')->nullable();
            $table->foreignId('faction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->boolean('is_open')->default(false);
            $table->boolean('requires_linked_account')->default(true);
            $table->boolean('requires_character')->default(false);
            $table->unsignedSmallInteger('min_account_age_days')->default(0);
            $table->unsignedSmallInteger('cooldown_days')->default(14);
            $table->smallInteger('sort')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('applications', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_applications_ulid');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('application_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('character_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('draft');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('decision_note_public', 2000)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['application_type_id', 'status', 'submitted_at'], 'idx_app_type_status');
            $table->index(['user_id', 'created_at'], 'idx_app_user');
        });
        // FR-APP-004: tür başına tek açık başvuru (generated column + unique).
        DB::statement("ALTER TABLE applications ADD COLUMN open_key VARCHAR(64) AS (IF(status IN ('draft','submitted','in_review','needs_info'), CONCAT(user_id, ':', application_type_id), NULL)) STORED AFTER status");
        DB::statement('ALTER TABLE applications ADD UNIQUE INDEX uq_applications_open_key (open_key)');

        Schema::create('application_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('field_key', 64);
            $table->string('question_label', 255);
            $table->text('answer')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['application_id', 'field_key'], 'uq_app_answers_field');
        });

        Schema::create('application_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('visibility', 16)->default('public');
            $table->text('body');
            $table->dateTime('created_at')->nullable();
            $table->index(['application_id', 'created_at'], 'idx_app_messages');
        });

        Schema::create('application_status_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16);
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('note_public', 2000)->nullable();
            $table->string('note_internal', 2000)->nullable();
            $table->dateTime('created_at');
        });
        // DATABASE §9.5: append-only.
        DB::unprepared("CREATE TRIGGER trg_app_history_no_update BEFORE UPDATE ON application_status_history FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'application_status_history is append-only'");
        DB::unprepared("CREATE TRIGGER trg_app_history_no_delete BEFORE DELETE ON application_status_history FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'application_status_history is append-only'");

        Schema::create('sanctions', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_sanctions_ulid');
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('mta_account_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('character_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 16);
            $table->string('public_reason', 500);
            $table->text('internal_note')->nullable();
            $table->string('evidence_url', 500)->nullable();
            $table->foreignId('rule_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('status', 16)->default('active');
            $table->boolean('appealable')->default(true);
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('revoked_at')->nullable();
            $table->string('revoke_reason', 500)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['user_id', 'status'], 'idx_sanctions_user');
            $table->index(['mta_account_id', 'status'], 'idx_sanctions_account');
            $table->index(['status', 'ends_at'], 'idx_sanctions_status_ends');
        });
        DB::statement('ALTER TABLE sanctions ADD CONSTRAINT chk_sanctions_target CHECK (user_id IS NOT NULL OR mta_account_id IS NOT NULL OR character_id IS NOT NULL)');

        Schema::create('sanction_appeals', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_sanction_appeals_ulid');
            $table->foreignId('sanction_id')->unique('uq_sanction_appeals_sanction')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->string('status', 16)->default('submitted');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('decision_note_public', 1000)->nullable();
            $table->text('internal_note')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['status', 'created_at'], 'idx_appeals_status');
        });

        Schema::create('mta_account_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mta_account_id')->constrained()->restrictOnDelete();
            $table->char('serial_hmac', 64);
            $table->string('label', 64)->nullable();
            $table->string('status', 32)->default('pending_approval');
            $table->foreignId('link_code_id')->nullable()->constrained('game_link_codes')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['mta_account_id', 'serial_hmac'], 'uq_mad_account_serial');
            $table->index('status', 'idx_mad_status');
        });

        Schema::table('factions', function (Blueprint $table): void {
            $table->foreign('application_type_id')->references('id')->on('application_types')->nullOnDelete();
        });
        Schema::table('game_jobs', function (Blueprint $table): void {
            $table->foreign('application_type_id')->references('id')->on('application_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_jobs', fn (Blueprint $table) => $table->dropForeign(['application_type_id']));
        Schema::table('factions', fn (Blueprint $table) => $table->dropForeign(['application_type_id']));

        DB::unprepared('DROP TRIGGER IF EXISTS trg_app_history_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_app_history_no_delete');

        foreach (['mta_account_devices', 'sanction_appeals', 'sanctions', 'application_status_history', 'application_messages', 'application_answers', 'applications', 'application_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
