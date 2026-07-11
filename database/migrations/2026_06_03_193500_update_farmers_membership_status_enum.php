<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE farmers
            SET membership_status = CASE membership_status
                WHEN 'Pending' THEN 'pending_application'
                WHEN 'Active' THEN 'active'
                WHEN 'Inactive' THEN 'pending_application'
                ELSE membership_status
            END
        ");

        if (DB::getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();
            Schema::create('farmers_new', function (Blueprint $table): void {
                $table->id();
                $table->string('farmer_code')->unique();
                $table->foreignId('barangay_id')->constrained()->restrictOnDelete();
                $table->foreignId('association_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('member_type_id')->constrained()->restrictOnDelete();
                $table->enum('membership_status', [
                    'pending_application',
                    'pending_documents',
                    'pending_verification',
                    'pending_payment',
                    'active',
                ])->default('pending_application');
                $table->string('record_origin')->default('Admin');
                $table->dateTime('registered_at')->nullable();
                $table->dateTime('activated_at')->nullable();
                $table->dateTime('inactive_at')->nullable();
                $table->text('inactive_reason')->nullable();
                $table->timestamps();
            });

            DB::table('farmers_new')->insertUsing(
                [
                    'id',
                    'farmer_code',
                    'barangay_id',
                    'association_id',
                    'member_type_id',
                    'membership_status',
                    'record_origin',
                    'registered_at',
                    'activated_at',
                    'inactive_at',
                    'inactive_reason',
                    'created_at',
                    'updated_at',
                ],
                DB::table('farmers')->select([
                    'id',
                    'farmer_code',
                    'barangay_id',
                    'association_id',
                    'member_type_id',
                    'membership_status',
                    'record_origin',
                    'registered_at',
                    'activated_at',
                    'inactive_at',
                    'inactive_reason',
                    'created_at',
                    'updated_at',
                ]),
            );

            Schema::drop('farmers');
            Schema::rename('farmers_new', 'farmers');
            Schema::enableForeignKeyConstraints();
        } else {
            DB::statement("
                ALTER TABLE farmers
                MODIFY membership_status ENUM(
                    'pending_application',
                    'pending_documents',
                    'pending_verification',
                    'pending_payment',
                    'active'
                ) NOT NULL DEFAULT 'pending_application'
            ");
        }
    }

    public function down(): void
    {
        DB::statement("
            UPDATE farmers
            SET membership_status = CASE membership_status
                WHEN 'pending_application' THEN 'Pending'
                WHEN 'pending_documents' THEN 'Pending'
                WHEN 'pending_verification' THEN 'Pending'
                WHEN 'pending_payment' THEN 'Pending'
                WHEN 'active' THEN 'Active'
                ELSE 'Pending'
            END
        ");

        if (DB::getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();
            Schema::create('farmers_new', function (Blueprint $table): void {
                $table->id();
                $table->string('farmer_code')->unique();
                $table->foreignId('barangay_id')->constrained()->restrictOnDelete();
                $table->foreignId('association_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('member_type_id')->constrained()->restrictOnDelete();
                $table->enum('membership_status', ['Pending', 'Active', 'Inactive'])->default('Pending');
                $table->string('record_origin')->default('Admin');
                $table->dateTime('registered_at')->nullable();
                $table->dateTime('activated_at')->nullable();
                $table->dateTime('inactive_at')->nullable();
                $table->text('inactive_reason')->nullable();
                $table->timestamps();
            });

            DB::table('farmers_new')->insertUsing(
                [
                    'id',
                    'farmer_code',
                    'barangay_id',
                    'association_id',
                    'member_type_id',
                    'membership_status',
                    'record_origin',
                    'registered_at',
                    'activated_at',
                    'inactive_at',
                    'inactive_reason',
                    'created_at',
                    'updated_at',
                ],
                DB::table('farmers')->select([
                    'id',
                    'farmer_code',
                    'barangay_id',
                    'association_id',
                    'member_type_id',
                    'membership_status',
                    'record_origin',
                    'registered_at',
                    'activated_at',
                    'inactive_at',
                    'inactive_reason',
                    'created_at',
                    'updated_at',
                ]),
            );

            Schema::drop('farmers');
            Schema::rename('farmers_new', 'farmers');
            Schema::enableForeignKeyConstraints();
        } else {
            DB::statement("
                ALTER TABLE farmers
                MODIFY membership_status ENUM('Pending', 'Active', 'Inactive')
                NOT NULL DEFAULT 'Pending'
            ");
        }
    }
};
