<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('type', 16);
            $table->decimal('amount', 19, 4);
            $table->string('symbol', 16)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('unit_price', 19, 4)->nullable();
            $table->decimal('cash_balance_after', 19, 4);
            $table->timestamp('created_at');

            $table->index(['client_id', 'created_at']);
        });

        DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_transactions_amount_positive CHECK (amount > 0)');
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_transactions_quantity_positive CHECK (quantity IS NULL OR quantity > 0)');
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_transactions_unit_price_positive CHECK (unit_price IS NULL OR unit_price > 0)');
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_trade_shape CHECK (
            (type IN ('buy', 'sell') AND symbol IS NOT NULL AND quantity IS NOT NULL AND unit_price IS NOT NULL)
            OR
            (type IN ('deposit', 'withdraw') AND symbol IS NULL AND quantity IS NULL AND unit_price IS NULL)
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
