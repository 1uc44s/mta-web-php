<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 10 — Web Push abonelikleri ve Akademi sınavları. docs/DATABASE.md §3.11a, §10.17.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->index('idx_push_user')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->char('endpoint_hash', 64)->unique('uq_push_endpoint');
            $table->string('public_key', 255);
            $table->text('auth_token');
            $table->string('content_encoding', 16)->default('aes128gcm');
            $table->string('user_agent_summary', 120)->nullable();
            $table->unsignedTinyInteger('failures')->default(0);
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('academy_quizzes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academy_topic_id')->unique('uq_quiz_topic')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->unsignedTinyInteger('pass_percent')->default(70);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->unsignedSmallInteger('cooldown_minutes')->default(60);
            $table->boolean('is_active')->default(false);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('academy_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academy_quiz_id')->constrained()->cascadeOnDelete();
            $table->string('body', 500);
            $table->smallInteger('sort')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('academy_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academy_question_id')->constrained()->cascadeOnDelete();
            $table->string('body', 255);
            $table->boolean('is_correct')->default(false);
            $table->smallInteger('sort')->default(0);
        });

        Schema::create('academy_attempts', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_academy_attempts_ulid');
            $table->foreignId('academy_quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score_percent');
            $table->boolean('passed');
            $table->json('answers');
            $table->dateTime('created_at');
            $table->index(['user_id', 'academy_quiz_id', 'created_at'], 'idx_attempts_user_quiz');
        });
    }

    public function down(): void
    {
        foreach (['academy_attempts', 'academy_options', 'academy_questions', 'academy_quizzes', 'push_subscriptions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
