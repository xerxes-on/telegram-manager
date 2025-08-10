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
        Schema::create('subscription_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('card_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('amount');
            $table->string('receipt_id');
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            $table->enum('type', ['subscription', 'renewal', 'plan_change'])->default('subscription');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'status']);
            $table->index('receipt_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_transactions');
    }
};