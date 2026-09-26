<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('farmer_documents') || Schema::hasColumn('farmer_documents', 'is_required')) {
            return;
        }

        Schema::table('farmer_documents', function (Blueprint $table): void {
            $table->boolean('is_required')->default(true)->after('document_type_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('farmer_documents') || ! Schema::hasColumn('farmer_documents', 'is_required')) {
            return;
        }

        Schema::table('farmer_documents', function (Blueprint $table): void {
            $table->dropColumn('is_required');
        });
    }
};
