<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * docs/DATABASE.md §3.1 users, §2 password_reset_tokens / sessions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->ulid()->unique();
            $table->string('username', 20)->unique();
            $table->string('email', 254)->unique();
            $table->dateTime('email_verified_at')->nullable();
            $table->string('password');
            $table->string('status', 32)->default('active')->index();
            $table->dateTime('suspended_until')->nullable();
            $table->string('suspension_reason', 500)->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->dateTime('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->string('theme', 16)->default('system');
            $table->boolean('reduced_effects')->default(false);
            $table->string('locale', 8)->default('tr');
            $table->dateTime('last_login_at')->nullable()->index();
            $table->dateTime('deletion_requested_at')->nullable();
            $table->datetimes();
            $table->index('created_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
