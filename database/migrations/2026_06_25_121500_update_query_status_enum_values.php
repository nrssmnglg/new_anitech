<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `queries`
                MODIFY `status` ENUM('Open', 'Answered', 'Closed', 'New', 'In Progress', 'Resolved', 'Escalated') NOT NULL DEFAULT 'Open'
            ");
        }

        DB::table('queries')
            ->where('status', 'Open')
            ->update(['status' => 'New']);

        DB::table('queries')
            ->where('status', 'Answered')
            ->update(['status' => 'In Progress']);

        DB::table('queries')
            ->where('status', 'Closed')
            ->update(['status' => 'Resolved']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `queries`
                MODIFY `status` ENUM('New', 'In Progress', 'Resolved', 'Escalated') NOT NULL DEFAULT 'New'
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `queries`
                MODIFY `status` ENUM('Open', 'Answered', 'Closed', 'New', 'In Progress', 'Resolved', 'Escalated') NOT NULL DEFAULT 'New'
            ");
        }

        DB::table('queries')
            ->where('status', 'New')
            ->update(['status' => 'Open']);

        DB::table('queries')
            ->where('status', 'In Progress')
            ->update(['status' => 'Answered']);

        DB::table('queries')
            ->where('status', 'Resolved')
            ->update(['status' => 'Closed']);

        DB::table('queries')
            ->where('status', 'Escalated')
            ->update(['status' => 'Open']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `queries`
                MODIFY `status` ENUM('Open', 'Answered', 'Closed') NOT NULL DEFAULT 'Open'
            ");
        }
    }
};
