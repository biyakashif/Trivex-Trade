<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_security', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('password'); // Hashed password
            $table->string('question1'); // Best friend name
            $table->string('answer1'); // Hashed answer
            $table->string('question2'); // Favorite pet name
            $table->string('answer2'); // Hashed answer
            $table->string('question3'); // Birth city name
            $table->string('answer3'); // Hashed answer
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_security');
    }
};