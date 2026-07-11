<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_transaction_id')->constrained('membership_transactions')->cascadeOnDelete();
            $table->foreignId('fee_schedule_id')->constrained('fee_schedules')->cascadeOnDelete();
            $table->unsignedInteger('year');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->enum('payment_status', ['Paid', 'Unpaid', 'Partial'])->default('Unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->boolean('mortuary_eligible')->default(false);
            $table->enum('status', ['Active', 'Expired', 'Inactive'])->default('Inactive');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_ledgers');
    }
};
