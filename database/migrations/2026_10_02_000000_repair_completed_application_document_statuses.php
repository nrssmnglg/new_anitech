<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('membership_transactions')
            || ! Schema::hasTable('payment_assessments')
            || ! Schema::hasTable('farmer_documents')) {
            return;
        }

        DB::table('membership_transactions')
            ->where('transaction_type', 'Application')
            ->where('status', 'Approved')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('payment_assessments')
                    ->whereColumn(
                        'payment_assessments.membership_transaction_id',
                        'membership_transactions.id',
                    )
                    ->where('payment_assessments.status', 'Paid');
            })
            ->select(['id', 'reviewed_by', 'reviewed_at'])
            ->orderBy('id')
            ->chunkById(100, function ($applications): void {
                foreach ($applications as $application) {
                    $values = [
                        'verification_status' => 'Verified',
                        'verified_at' => $application->reviewed_at ?? now(),
                        'updated_at' => now(),
                    ];

                    if (Schema::hasColumn('farmer_documents', 'is_received')) {
                        $values['is_received'] = true;
                    }

                    if ($application->reviewed_by !== null) {
                        $values['verified_by'] = $application->reviewed_by;
                    }

                    DB::table('farmer_documents')
                        ->where('membership_transaction_id', $application->id)
                        ->where('is_required', true)
                        ->where('verification_status', '!=', 'Verified')
                        ->update($values);
                }
            });
    }

    public function down(): void
    {
        // Historical verification state cannot be reconstructed safely.
    }
};
