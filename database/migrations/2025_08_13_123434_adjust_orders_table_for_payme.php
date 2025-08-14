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
        Schema::table('orders', function (Blueprint $table) {
            // Change price to bigInteger for PayMe large amount compatibility
            $table->bigInteger('price')->change();
            
            // Add index on status field for better performance (frequent updates)
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert price to regular integer
            $table->integer('price')->change();
            
            // Drop status index
            $table->dropIndex(['status']);
        });
    }
};
