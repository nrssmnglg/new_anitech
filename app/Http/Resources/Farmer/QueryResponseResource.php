<?php

namespace App\Http\Resources\Farmer;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueryResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isFromStaff = $this->responder
            ? ((int) ($this->responder->farmer_id ?? 0) !== (int) ($this->inquiry?->farmer_id ?? 0)
                && in_array($this->responder->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true))
            : (bool) ($this->is_from_staff ?? false);

        return [
            'id' => $this->id,
            'message' => $this->message,
            'responded_at' => optional($this->responded_at)->toIso8601String(),
            'is_from_staff' => $isFromStaff,
            'sender_name' => $this->responder?->name,
            'responder' => $this->responder ? [
                'id' => $this->responder->id,
                'name' => $this->responder->name,
                'role' => $this->responder->role,
                'farmer_id' => $this->responder->farmer_id,
            ] : null,
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments
                ->map(fn (Attachment $attachment): array => AttachmentResource::serialize($attachment, $this->inquiry, $this->resource))
                ->values()
                ->all()),
        ];
    }
}
