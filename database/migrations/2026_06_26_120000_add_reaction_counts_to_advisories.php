<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisories', function (Blueprint $table) {
            $table->unsignedInteger('like_count')->default(0)->after('published_at');
            $table->unsignedInteger('dislike_count')->default(0)->after('like_count');
        });

        Schema::create('advisory_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisory_id')->constrained('advisories')->cascadeOnDelete();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->enum('reaction', ['like', 'dislike']);
            $table->timestamps();
            $table->unique(['advisory_id', 'farmer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisory_reactions');

        Schema::table('advisories', function (Blueprint $table) {
            $table->dropColumn(['like_count', 'dislike_count']);
        });
    }
};
