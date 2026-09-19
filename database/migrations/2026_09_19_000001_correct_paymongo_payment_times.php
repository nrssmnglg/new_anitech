<?php

use App\Models\PayMongoWebhookEvent;
use App\Services\Payments\PayMongoPaidAt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $paidAt = app(PayMongoPaidAt::class);

        PayMongoWebhookEvent::query()
            ->where('status', 'processed')
            ->whereNotNull('payment_id')
            ->orderBy('id')
            ->chunkById(100, function ($events) use ($paidAt): void {
                foreach ($events as $event) {
                    $timestamp = $paidAt->fromPayload($event->payload ?? []);
                    if ($timestamp === null) {
                        continue;
                    }

                    DB::table('payments')
                        ->where('id', $event->payment_id)
                        ->update(['paid_at' => $timestamp->toDateTimeString()]);
                }
            });
    }

    public function down(): void
    {
        // The original webhook timestamp remains the source of truth.
    }
};
