<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class NotificationDispatchService
{
    public function queue(NotificationType|string $type, iterable $recipients, array $payload = [], ?int $createdBy = null): array
    {
        $notificationType = $this->normalizeType($type);
        $queuedAt = CarbonImmutable::now()->toDateTimeString();
        $recipientRecords = [];

        foreach ($recipients as $recipient) {
            $recipientData = $this->normalize($recipient);
            $recipientRecords[] = [
                'user_id' => $recipientData['user_id'] ?? null,
                'farmer_id' => $recipientData['farmer_id'] ?? null,
                'recipient_address' => $recipientData['recipient_address'] ?? $recipientData['email'] ?? $recipientData['mobile_number'] ?? null,
                'status' => 'pending',
            ];
        }

        return [
            'type' => $notificationType->value,
            'subject' => $payload['subject'] ?? $notificationType->label(),
            'message' => $payload['message'] ?? '',
            'payload' => $payload,
            'status' => 'queued',
            'queued_at' => $queuedAt,
            'created_by' => $createdBy,
            'recipients' => $recipientRecords,
        ];
    }

    public function persist(NotificationType|string $type, iterable $recipients, array $payload = [], ?int $createdBy = null): array
    {
        return $this->persistQueued($this->queue($type, $recipients, $payload, $createdBy));
    }

    public function persistQueued(array|object $notification): array
    {
        $data = $this->normalize($notification);
        $timestamp = CarbonImmutable::now()->toDateTimeString();

        return DB::transaction(function () use ($data, $timestamp): array {
            $notificationId = DB::table('notifications')->insertGetId([
                'type' => $data['type'],
                'channel' => $data['channel'] ?? 'database',
                'subject' => $data['subject'],
                'message' => $data['message'],
                'payload' => isset($data['payload']) ? json_encode($data['payload'], JSON_THROW_ON_ERROR) : null,
                'status' => $data['status'] ?? 'queued',
                'queued_at' => $data['queued_at'] ?? $timestamp,
                'sent_at' => $data['sent_at'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $recipientRows = array_map(
                fn (array $recipient): array => [
                    'notification_id' => $notificationId,
                    'user_id' => $recipient['user_id'] ?? null,
                    'farmer_id' => $recipient['farmer_id'] ?? null,
                    'recipient_address' => $recipient['recipient_address'] ?? null,
                    'status' => $recipient['status'] ?? 'pending',
                    'delivered_at' => $recipient['delivered_at'] ?? null,
                    'read_at' => $recipient['read_at'] ?? null,
                    'failed_at' => $recipient['failed_at'] ?? null,
                    'failure_reason' => $recipient['failure_reason'] ?? null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                $data['recipients'] ?? [],
            );

            if ($recipientRows !== []) {
                DB::table('notification_recipients')->insert($recipientRows);
            }

            return array_merge($data, [
                'id' => $notificationId,
                'recipients' => $recipientRows,
            ]);
        });
    }

    public function markSent(array|object $notification): array
    {
        $data = $this->normalize($notification);

        return array_merge($data, [
            'status' => 'sent',
            'sent_at' => CarbonImmutable::now()->toDateTimeString(),
        ]);
    }

    public function markRecipientDelivered(array|object $recipient): array
    {
        $data = $this->normalize($recipient);

        return array_merge($data, [
            'status' => 'delivered',
            'delivered_at' => CarbonImmutable::now()->toDateTimeString(),
        ]);
    }

    public function markRecipientFailed(array|object $recipient, string $reason): array
    {
        $data = $this->normalize($recipient);

        return array_merge($data, [
            'status' => 'failed',
            'failed_at' => CarbonImmutable::now()->toDateTimeString(),
            'failure_reason' => $reason,
        ]);
    }

    private function normalizeType(NotificationType|string $type): NotificationType
    {
        return $type instanceof NotificationType ? $type : NotificationType::from($type);
    }

    private function normalize(array|object $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (method_exists($payload, 'toArray')) {
            return $payload->toArray();
        }

        return get_object_vars($payload);
    }
}
