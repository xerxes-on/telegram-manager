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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('price');
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->index(['price', 'status']);
            $table->index('client_id');
            $table->index('created_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
