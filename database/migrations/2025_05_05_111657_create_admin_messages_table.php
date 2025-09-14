<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message');
            $table->boolean('active')->default(true); // To enable/disable messages
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_messages');
    }
};