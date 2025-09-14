<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_types', function (Blueprint $table) {
            $table->id();
            $table->string('coin_name')->unique(); // e.g., Bitcoin, Ethereum, Tether
            $table->string('symbol')->unique(); // e.g., BTC, ETH, USDT
            $table->timestamps();
        });

        // Seed the table with initial cryptocurrencies
        \DB::table('coin_types')->insert([
            ['coin_name' => 'Bitcoin', 'symbol' => 'btc'],
            ['coin_name' => 'Ethereum', 'symbol' => 'eth'],
            ['coin_name' => 'Tether', 'symbol' => 'usdt'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_types');
    }
};