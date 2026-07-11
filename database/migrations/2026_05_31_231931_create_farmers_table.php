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
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_code')->unique();
            $table->foreignId('barangay_id')->constrained()->restrictOnDelete();
            $table->foreignId('association_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('member_type_id')->constrained()->restrictOnDelete();

            $table->enum('membership_status', ['Pending', 'Active', 'Inactive'])->default('Pending');
            $table->string('record_origin')->default('Admin');

            $table->dateTime('registered_at')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('inactive_at')->nullable();
            $table->text('inactive_reason')->nullable();

            $table->timestamps();

            $table->index('barangay_id');
            $table->index('association_id');
            $table->index('member_type_id');
            $table->index('membership_status');
            $table->index('registered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};
