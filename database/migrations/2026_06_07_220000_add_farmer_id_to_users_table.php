<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'farmer_id')) {
                $table->foreignId('farmer_id')->nullable()->after('email')->constrained()->nullOnDelete();
                $table->index('farmer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'farmer_id')) {
                $table->dropConstrainedForeignId('farmer_id');
            }
        });
    }
};
