<?php

declare(strict_types=1);

use App\Support\Database\ContentColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 5 — duyurular, Akademi ilerlemesi, kural onayı, mesaj şablonları ve gönderim logu.
 * docs/DATABASE.md §10.12, §10.15, §10.17, §10.18.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('title', 150);
            $table->text('body');
            $table->string('priority', 16)->default('info');
            $table->string('audience', 16)->default('all');
            $table->json('audience_role_ids')->nullable();
            $table->json('channels');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('email_dispatched_at')->nullable();
            ContentColumns::publishable($table, withSeo: false);
            $table->index(['status', 'starts_at']);
        });

        Schema::create('announcement_reads', function (Blueprint $table): void {
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('read_at');
            $table->primary(['announcement_id', 'user_id']);
        });

        Schema::create('academy_progress', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academy_topic_id')->constrained()->cascadeOnDelete();
            $table->dateTime('completed_at');
            $table->primary(['user_id', 'academy_topic_id']);
        });

        Schema::create('rule_acknowledgements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('version', 32);
            $table->dateTime('acknowledged_at');
            $table->unique(['user_id', 'version']);
        });

        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100);
            $table->string('channel', 32);
            $table->string('locale', 8)->default('tr');
            $table->string('name', 150);
            $table->string('subject', 200)->nullable();
            $table->text('body');
            $table->json('variables');
            $table->string('provider_template_name', 100)->nullable();
            $table->string('provider_language', 16)->nullable();
            $table->json('provider_params_map')->nullable();
            $table->string('status', 16)->default('active');
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['key', 'channel', 'locale'], 'uq_mt_key_channel_locale');
        });

        Schema::create('message_template_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('subject', 200)->nullable();
            $table->text('body');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at');
        });

        Schema::create('message_dispatches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_key', 100);
            $table->string('channel', 32);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_masked', 100)->nullable();
            $table->string('status', 24);
            $table->string('provider_message_id', 128)->nullable();
            $table->string('error', 500)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('queued_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->index(['user_id', 'queued_at']);
            $table->index(['status', 'queued_at']);
        });
    }

    public function down(): void
    {
        foreach (['message_dispatches', 'message_template_revisions', 'message_templates', 'rule_acknowledgements', 'academy_progress', 'announcement_reads', 'announcements'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
