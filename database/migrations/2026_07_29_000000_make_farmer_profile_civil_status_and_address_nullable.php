<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table): void {
            $table->enum('civil_status', ['Single', 'Married', 'Widowed', 'Separated'])->nullable()->change();
            $table->text('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table): void {
            $table->enum('civil_status', ['Single', 'Married', 'Widowed', 'Separated'])->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
        });
    }
};
