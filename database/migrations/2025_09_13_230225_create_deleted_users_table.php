<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deleted_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->nullable();
            $table->string('status')->nullable();
            $table->rememberToken();
            $table->timestamp('last_activity')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->boolean('loss_applied')->default(false);
            $table->timestamp('deleted_at');
            $table->unsignedBigInteger('deleted_by_admin_id');
            $table->json('original_user_data')->nullable(); // Store complete user data as JSON
            $table->timestamps();

            $table->foreign('deleted_by_admin_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_users');
    }
};
