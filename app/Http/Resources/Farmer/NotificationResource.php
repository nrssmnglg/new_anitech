<?php

namespace App\Http\Resources\Farmer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'recipient_id' => $this->recipient_id,
            'notification_id' => $this->notification_id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->recipient_status,
            'is_read' => $this->is_read,
            'is_priority' => (bool) ($this->is_priority ?? false),
            'read_at' => optional($this->read_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'payload' => $this->payload_data ?? [],
            'target_url' => $this->target_url ?? null,
            'module' => $this->module ?? 'account_alerts',
            'module_label' => $this->module_label ?? 'Account Alerts',
            'action' => $this->action ?? null,
        ];
    }
}
