<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('associations', function (Blueprint $table): void {
            $table->foreignId('president_farmer_id')
                ->nullable()
                ->after('president_name')
                ->constrained('farmers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('associations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('president_farmer_id');
        });
    }
};
