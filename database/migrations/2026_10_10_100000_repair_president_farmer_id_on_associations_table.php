<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('associations', 'president_farmer_id')) {
            return;
        }

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
        // Intentionally left unchanged. This migration repairs production
        // databases where the original column migration was recorded without
        // the column actually being present.
    }
};
