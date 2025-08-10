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
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('days')->after('price')->nullable();
        });

        // Update existing plans with days based on their name
        DB::table('plans')->where('name', 'one-week-free')->update(['days' => 7]);
        DB::table('plans')->where('name', 'one-month')->update(['days' => 30]);
        DB::table('plans')->where('name', 'two-months')->update(['days' => 60]);
        DB::table('plans')->where('name', 'six-months')->update(['days' => 180]);
        DB::table('plans')->where('name', 'one-year')->update(['days' => 365]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('days');
        });
    }
};