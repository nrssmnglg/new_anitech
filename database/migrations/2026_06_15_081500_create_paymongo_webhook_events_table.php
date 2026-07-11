<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paymongo_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('paymongo_event_id')->nullable()->index();
            $table->string('event_type')->nullable()->index();
            $table->string('resource_id')->nullable()->index();
            $table->string('resource_type')->nullable();
            $table->boolean('livemode')->default(false);
            $table->string('status')->default('received')->index();
            $table->text('message')->nullable();
            $table->string('reference_no')->nullable()->index();
            $table->foreignId('payment_assessment_id')->nullable()->constrained('payment_assessments')->nullOnDelete();
            $table->foreignId('membership_application_id')->nullable()->constrained('membership_transactions')->nullOnDelete();
            $table->foreignId('renewal_request_id')->nullable()->constrained('membership_transactions')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('amount', 10, 2)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paymongo_webhook_events');
    }
};
