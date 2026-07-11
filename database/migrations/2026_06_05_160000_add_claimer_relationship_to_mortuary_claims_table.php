<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mortuary_claims', function (Blueprint $table) {
            $table->string('claimer_relationship')->nullable()->after('claimer_last_name');
        });
    }

    public function down(): void
    {
        Schema::table('mortuary_claims', function (Blueprint $table) {
            $table->dropColumn('claimer_relationship');
        });
    }
};
