<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('farmer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_name');
            $table->string('module')->nullable();
            $table->string('page')->nullable();
            $table->string('route_name')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('session_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['event_name', 'occurred_at']);
            $table->index(['module', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index(['farmer_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
