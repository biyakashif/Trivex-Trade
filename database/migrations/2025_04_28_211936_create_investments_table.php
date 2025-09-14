<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plan'); // e.g., '7_days', '15_days'
            $table->decimal('amount', 15, 2); // Invested amount
            $table->decimal('profit', 15, 2); // Expected profit
            $table->timestamp('starts_at'); // Investment start date
            $table->timestamp('ends_at'); // Investment end date
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};