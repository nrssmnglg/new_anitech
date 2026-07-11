<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mortuary_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_ledger_id')->constrained('membership_ledgers')->cascadeOnDelete();
            $table->string('claim_reference')->unique();
            $table->string('claimer_first_name');
            $table->string('claimer_middle_name')->nullable();
            $table->string('claimer_last_name');
            $table->string('claimer_contact_number')->nullable();
            $table->text('claimer_address')->nullable();
            $table->decimal('claim_amount', 10, 2)->default(0);
            $table->date('claim_date');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Released'])->default('Pending');
            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('mortuary_claim_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mortuary_claim_id')->constrained('mortuary_claims')->cascadeOnDelete();
            $table->foreignId('requirement_type_id')->constrained('claim_requirement_types')->cascadeOnDelete();
            $table->boolean('is_received')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('remarks')->nullable();
        });

        Schema::create('mortuary_claim_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mortuary_claim_id')->constrained('mortuary_claims')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('reference_no')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mortuary_claim_disbursements');
        Schema::dropIfExists('mortuary_claim_requirements');
        Schema::dropIfExists('mortuary_claims');
    }
};
