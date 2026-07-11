<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->enum('status', ['Draft', 'Published', 'Archived'])->default('Draft');
            $table->string('audience_type')->nullable();
            $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('member_type_id')->nullable()->constrained('member_types')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('query_categories')->cascadeOnDelete();
            $table->string('subject');
            $table->longText('message');
            $table->enum('status', ['Open', 'Answered', 'Closed'])->default('Open');
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('query_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('query_id')->constrained('queries')->cascadeOnDelete();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('message');
            $table->timestamp('responded_at')->nullable();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('module_type');
            $table->unsignedBigInteger('module_id');
            $table->string('original_name')->nullable();
            $table->string('file_path');
            $table->timestamp('uploaded_at')->nullable();
            $table->index(['module_type', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('query_responses');
        Schema::dropIfExists('queries');
        Schema::dropIfExists('advisories');
    }
};
