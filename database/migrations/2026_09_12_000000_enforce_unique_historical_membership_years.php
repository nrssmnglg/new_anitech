<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['renewal' => ['transaction_type', 'Renewal'], 'historical' => ['source', 'legacy']] as $label => [$column, $value]) {
            if (DB::table('membership_transactions')->where($column, $value)->whereNotNull('year')
                ->select('farmer_id', 'year')->groupBy('farmer_id', 'year')->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException('Duplicate ' . $label . ' farmer/year transactions must be reviewed before adding uniqueness constraints. No records were deleted.');
            }
        }
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->unsignedInteger('renewal_year_key')->nullable()
                ->virtualAs("CASE WHEN transaction_type = 'Renewal' THEN year ELSE NULL END");
            $table->unsignedInteger('historical_year_key')->nullable()
                ->virtualAs("CASE WHEN source = 'legacy' THEN year ELSE NULL END");
            $table->unique(['farmer_id', 'renewal_year_key'], 'membership_renewal_year_unique');
            $table->unique(['farmer_id', 'historical_year_key'], 'membership_historical_year_unique');
        });
    }

    public function down(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->dropUnique('membership_renewal_year_unique');
            $table->dropUnique('membership_historical_year_unique');
            $table->dropColumn(['renewal_year_key', 'historical_year_key']);
        });
    }
};
