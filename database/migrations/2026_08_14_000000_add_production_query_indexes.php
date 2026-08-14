<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->index(
                ['transaction_type', 'status', 'submitted_at'],
                'membership_transactions_queue_index'
            );
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['status', 'paid_at'], 'payments_status_paid_at_index');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(['status', 'queued_at'], 'notifications_queue_index');
        });

        Schema::table('notification_recipients', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'notification_recipients_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->dropIndex('membership_transactions_queue_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_status_paid_at_index');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_queue_index');
        });

        Schema::table('notification_recipients', function (Blueprint $table): void {
            $table->dropIndex('notification_recipients_status_index');
        });
    }
};
