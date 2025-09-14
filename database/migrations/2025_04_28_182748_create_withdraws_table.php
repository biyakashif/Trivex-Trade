<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdraws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('coin_id')->nullable()->constrained('coin_types')->onDelete('set null'); // For crypto withdrawals
            $table->decimal('amount_withdraw', 15, 8); // Withdrawal amount
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('bank_account_number')->nullable(); // Used for both bank account number and crypto wallet address
            $table->string('account_holder_name')->nullable(); // For bank withdrawals
            $table->string('bank_name')->nullable(); // For bank withdrawals
            $table->string('crypto_wallet')->nullable(); // Redundant but kept for clarity (same as bank_account_number for crypto)
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraws');
    }
};