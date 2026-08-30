<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PaymentStatus supports statuses beyond the three original enum values.
        // A string column keeps the database aligned with that domain enum.
        DB::statement("ALTER TABLE payments MODIFY status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY status ENUM('Pending', 'Verified', 'Rejected') NOT NULL DEFAULT 'Pending'");
    }
};
