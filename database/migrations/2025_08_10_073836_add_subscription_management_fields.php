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
            $table->foreignId('previous_subscription_id')->nullable()->after('plan_id')
                ->constrained('subscriptions')->nullOnDelete();
            $table->boolean('is_renewal')->default(false)->after('previous_subscription_id');
            $table->timestamp('reminder_sent_at')->nullable()->after('last_payment_error');
            $table->integer('reminder_count')->default(0)->after('reminder_sent_at');
            $table->index(['status', 'expires_at', 'reminder_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['previous_subscription_id']);
            $table->dropColumn(['previous_subscription_id', 'is_renewal', 'reminder_sent_at', 'reminder_count']);
        });
    }
};