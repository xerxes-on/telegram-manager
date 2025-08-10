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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->boolean('status')->default(true);
            $table->string('receipt_id');
            $table->date('expires_at');
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->index(['expires_at', 'status']);
            $table->index('client_id');
            $table->index('plan_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
