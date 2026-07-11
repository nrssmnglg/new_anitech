<?php

namespace App\Http\Resources\Farmer;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getRouteKey(),
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'status_key' => str($this->status)->lower()->replace(' ', '_')->toString(),
            'archived_at' => optional($this->archived_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'farmer_name' => $this->farmer?->full_name,
            'last_activity_at' => optional($this->last_activity_at ?? $this->created_at)->toIso8601String(),
            'last_staff_reply_at' => optional($this->last_staff_reply_at)->toIso8601String(),
            'last_staff_reply_excerpt' => $this->last_staff_reply_excerpt,
            'has_unread_reply' => (bool) ($this->has_unread_reply ?? false),
            'unread_reply_count' => (int) ($this->unread_reply_count ?? 0),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'code' => $this->category->code,
                'name' => $this->category->name,
            ] : null,
            'responses_count' => $this->whenCounted('responses'),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments
                ->map(fn (Attachment $attachment): array => AttachmentResource::serialize($attachment, $this->resource))
                ->values()
                ->all()),
        ];
    }
}
