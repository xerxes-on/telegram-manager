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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('payment_retry_count')->default(0)->after('plan_id');
            $table->timestamp('last_payment_attempt')->nullable()->after('payment_retry_count');
            $table->text('last_payment_error')->nullable()->after('last_payment_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['payment_retry_count', 'last_payment_attempt', 'last_payment_error']);
        });
    }
};