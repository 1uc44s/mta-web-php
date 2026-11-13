<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 9 — ekonomi ve ödeme. docs/DATABASE.md §6.1–6.9, §7 (append-only trigger).
 * `wallets.balance` yalnızca `LedgerService` tarafından yazılır; negatif bakiye DB CHECK ile de engellenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_types', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 32)->unique('uq_wallet_types_key');
            $table->string('name', 64);
            $table->string('unit_label', 16);
            $table->boolean('is_purchasable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('wallet_type_id')->constrained()->restrictOnDelete();
            $table->bigInteger('balance')->default(0);
            $table->unsignedInteger('version')->default(0);
            $table->string('status', 16)->default('active');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['user_id', 'wallet_type_id'], 'uq_wallets_user_type');
        });
        DB::statement('ALTER TABLE wallets ADD CONSTRAINT chk_wallets_balance CHECK (balance >= 0)');

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_wt_ulid');
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->string('direction', 8);
            $table->bigInteger('amount');
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->string('reason', 32);
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->unique('uq_wt_reversal_of')->constrained('wallet_transactions')->restrictOnDelete();
            $table->string('idempotency_key', 100)->unique('uq_wt_idempotency');
            $table->string('description', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('created_at');
            $table->index(['wallet_id', 'created_at'], 'idx_wt_wallet_created');
            $table->index(['source_type', 'source_id'], 'idx_wt_source');
        });
        DB::statement("ALTER TABLE wallet_transactions ADD CONSTRAINT chk_wt_direction CHECK (direction IN ('credit','debit'))");
        DB::statement('ALTER TABLE wallet_transactions ADD CONSTRAINT chk_wt_amount CHECK (amount > 0)');
        DB::statement('ALTER TABLE wallet_transactions ADD CONSTRAINT chk_wt_balance_after CHECK (balance_after >= 0)');
        DB::unprepared("CREATE TRIGGER trg_wallet_transactions_no_update BEFORE UPDATE ON wallet_transactions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'wallet_transactions is append-only'");
        DB::unprepared("CREATE TRIGGER trg_wallet_transactions_no_delete BEFORE DELETE ON wallet_transactions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'wallet_transactions is append-only'");

        Schema::create('payment_packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_type_id')->constrained()->restrictOnDelete();
            $table->string('name', 64);
            $table->bigInteger('credit_amount');
            $table->bigInteger('bonus_amount')->default(0);
            $table->bigInteger('price_minor');
            $table->char('currency', 3)->default('TRY');
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
        DB::statement('ALTER TABLE payment_packages ADD CONSTRAINT chk_pp_price CHECK (price_minor > 0 AND credit_amount > 0 AND bonus_amount >= 0)');

        Schema::create('payment_orders', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_po_ulid');
            $table->string('order_number', 32)->unique('uq_po_order_number');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_package_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('provider', 32);
            $table->string('provider_order_id', 128)->nullable();
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->bigInteger('credit_amount');
            $table->string('status', 24)->default('created');
            $table->bigInteger('refunded_minor')->default(0);
            $table->string('idempotency_key', 100)->unique('uq_po_idempotency');
            $table->string('failure_reason', 255)->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['provider', 'provider_order_id'], 'uq_po_provider_order');
            $table->index(['user_id', 'created_at'], 'idx_po_user_created');
            $table->index(['status', 'created_at'], 'idx_po_status_created');
        });
        DB::statement("ALTER TABLE payment_orders ADD CONSTRAINT chk_po_status CHECK (status IN ('created','pending','requires_action','succeeded','failed','cancelled','refunded','partially_refunded','disputed'))");

        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32);
            $table->string('provider_event_id', 128);
            $table->foreignId('payment_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('event_type', 64);
            $table->boolean('signature_valid');
            $table->json('payload');
            $table->string('status', 16)->default('received');
            $table->string('error', 500)->nullable();
            $table->dateTime('received_at');
            $table->dateTime('processed_at')->nullable();
            $table->unique(['provider', 'provider_event_id'], 'uq_pe_provider_event');
        });

        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_refunds_ulid');
            $table->foreignId('payment_order_id')->constrained()->restrictOnDelete();
            $table->bigInteger('amount_minor');
            $table->string('reason', 500);
            $table->string('status', 24)->default('requested');
            $table->string('provider_refund_id', 128)->nullable();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
        DB::statement('ALTER TABLE refunds ADD CONSTRAINT chk_refunds_amount CHECK (amount_minor > 0)');

        Schema::create('wallet_adjustment_requests', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_war_ulid');
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->string('direction', 8);
            $table->bigInteger('amount');
            $table->string('reason', 1000);
            $table->string('status', 16)->default('pending');
            $table->boolean('requires_second_approval');
            $table->foreignId('reversal_of_id')->nullable()->constrained('wallet_transactions')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_note', 1000)->nullable();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->restrictOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['status', 'created_at'], 'idx_war_status');
        });
        DB::statement('ALTER TABLE wallet_adjustment_requests ADD CONSTRAINT chk_war_amount CHECK (amount > 0)');

        Schema::create('wallet_reconciliation_runs', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique('uq_wrr_ulid');
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('wallets_checked')->default(0);
            $table->unsignedInteger('mismatches')->default(0);
            $table->bigInteger('total_balance')->default(0);
            $table->bigInteger('ledger_net')->default(0);
            $table->json('details')->nullable();
            $table->dateTime('started_at')->index('idx_wrr_started');
            $table->dateTime('finished_at')->nullable();
        });
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_wallet_transactions_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_wallet_transactions_no_delete');

        foreach (['wallet_reconciliation_runs', 'wallet_adjustment_requests', 'refunds', 'payment_events', 'payment_orders', 'payment_packages', 'wallet_transactions', 'wallets', 'wallet_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
