<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_transaction_id')->constrained('membership_transactions')->cascadeOnDelete();
            $table->foreignId('fee_schedule_id')->constrained('fee_schedules')->cascadeOnDelete();
            $table->decimal('total_amount_due', 10, 2)->default(0);
            $table->enum('status', ['Pending', 'Paid', 'Partially Paid'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_assessments');
    }
};
