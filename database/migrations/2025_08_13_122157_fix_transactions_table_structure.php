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
        Schema::table('transactions', function (Blueprint $table) {
            // Change data types for better PayMe compatibility
            $table->bigInteger('paycom_time')->nullable()->change();
            $table->bigInteger('perform_time_unix')->nullable()->change();
            $table->bigInteger('cancel_time')->nullable()->change();
            $table->bigInteger('amount')->nullable()->change();
            
            // Add missing indexes for better performance
            $table->index('paycom_transaction_id');
            $table->index('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Revert data type changes
            $table->string('paycom_time', 13)->nullable()->change();
            $table->string('perform_time_unix', 13)->nullable()->change();
            $table->string('cancel_time', 13)->nullable()->change();
            $table->integer('amount')->nullable()->change();
            
            // Drop added indexes
            $table->dropIndex(['paycom_transaction_id']);
            $table->dropIndex(['state']);
        });
    }
};
