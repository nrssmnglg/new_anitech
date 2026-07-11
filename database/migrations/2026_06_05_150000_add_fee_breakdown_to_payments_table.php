<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('membership_fee', 10, 2)->default(0)->after('amount_paid');
            $table->decimal('annual_due', 10, 2)->default(0)->after('membership_fee');
            $table->decimal('mortuary_fee', 10, 2)->default(0)->after('annual_due');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['membership_fee', 'annual_due', 'mortuary_fee']);
        });
    }
};
