<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_assessments', function (Blueprint $table) {
            $table->decimal('membership_fee', 10, 2)->default(0)->after('total_amount_due');
            $table->decimal('annual_due', 10, 2)->default(0)->after('membership_fee');
            $table->decimal('mortuary_fee', 10, 2)->default(0)->after('annual_due');
        });
    }

    public function down(): void
    {
        Schema::table('payment_assessments', function (Blueprint $table) {
            $table->dropColumn(['membership_fee', 'annual_due', 'mortuary_fee']);
        });
    }
};
