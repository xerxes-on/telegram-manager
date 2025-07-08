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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('telegram_id');
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('username')->nullable();
            $table->string('state')->nullable();
            $table->string('chat_id');
            $table->string('lang', 5);
            $table->timestamps();
            $table->index(['phone_number', 'chat_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
