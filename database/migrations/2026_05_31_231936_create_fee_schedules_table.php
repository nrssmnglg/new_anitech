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
        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_type_id')->constrained()->restrictOnDelete();
            $table->year('year');
            $table->decimal('membership_fee', 10, 2)->default(0);
            $table->decimal('annual_due', 10, 2)->default(0);
            $table->decimal('mortuary_fee', 10, 2)->default(0);
            $table->date('renewal_deadline')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('member_type_id');
            $table->index(['effective_from', 'effective_to']);
            $table->unique(['member_type_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_schedules');
    }
};
