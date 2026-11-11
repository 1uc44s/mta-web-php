<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Yetki, branş ve denetim tabloları — docs/DATABASE.md §3.2–3.7, §7, §11.3–11.4 (Faz 4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique('uq_roles_slug');
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->string('color', 16)->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_staff')->default(true);
            $table->boolean('is_system')->default(false);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique('uq_permissions_key');
            $table->string('group', 50);
            $table->string('description', 255);
            $table->boolean('is_sensitive')->default(false);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('expires_at')->nullable()->index('idx_user_roles_expires_at');
            $table->dateTime('created_at')->nullable();
            $table->unique(['user_id', 'role_id'], 'uq_user_roles_user_role');
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique('uq_branches_slug');
            $table->string('name', 100);
            $table->string('type', 32)->index('idx_branches_type');
            $table->foreignId('faction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('branch_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->boolean('is_lead')->default(false);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->unique(['branch_id', 'user_id'], 'uq_branch_members');
            $table->index('user_id', 'idx_branch_members_user');
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_audit_ulid');
            $table->string('actor_type', 16);
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('action', 64);
            $table->string('target_type', 64)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('severity', 16);
            $table->json('changes')->nullable();
            $table->string('reason', 1000)->nullable();
            $table->string('ip_masked', 64)->nullable();
            $table->string('user_agent_summary', 120)->nullable();
            $table->char('request_id', 26)->nullable();
            $table->char('prev_hash', 64)->nullable();
            $table->char('hash', 64);
            $table->dateTime('created_at');
            $table->index(['actor_id', 'created_at']);
            $table->index(['target_type', 'target_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['severity', 'created_at']);
        });

        Schema::create('admin_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('route_name', 100);
            $table->string('method', 8);
            $table->string('target_type', 64)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('ip_masked', 64)->nullable();
            $table->dateTime('created_at');
            $table->index(['user_id', 'created_at']);
        });

        // docs/DATABASE.md §7 — audit_logs append-only.
        foreach (['update' => 'UPDATE', 'delete' => 'DELETE'] as $name => $event) {
            DB::unprepared("CREATE TRIGGER trg_audit_logs_no_{$name} BEFORE {$event} ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only'");
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_logs_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_logs_no_delete');

        foreach (['admin_activity_logs', 'audit_logs', 'branch_members', 'branches', 'user_roles', 'role_permissions', 'permissions', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
