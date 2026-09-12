<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('payments', function (Blueprint $table): void {
                $table->string('status', 30)->default('Pending')->change();
            });

            return;
        }

        // PaymentStatus supports statuses beyond the three original enum values.
        // A string column keeps the database aligned with that domain enum.
        DB::statement("ALTER TABLE payments MODIFY status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('payments', function (Blueprint $table): void {
                $table->enum('status', ['Pending', 'Verified', 'Rejected'])->default('Pending')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE payments MODIFY status ENUM('Pending', 'Verified', 'Rejected') NOT NULL DEFAULT 'Pending'");
    }
};
