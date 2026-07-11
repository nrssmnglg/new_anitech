<?php

namespace App\Http\Resources\Farmer;

use App\Services\Routing\PublicRouteKeyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvisoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentReaction = $this->whenLoaded('reactions', function () {
            return $this->reactions->first()?->reaction;
        });

        return [
            'id' => $this->slug ?: app(PublicRouteKeyService::class)->encode($this->id),
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'status' => $this->status,
            'audience_type' => $this->audience_type,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'like_count' => (int) ($this->like_count ?? 0),
            'dislike_count' => (int) ($this->dislike_count ?? 0),
            'farmer_reaction' => $currentReaction,
            'barangay' => $this->barangay ? [
                'id' => $this->barangay->id,
                'name' => $this->barangay->name,
            ] : null,
            'member_type' => $this->memberType ? [
                'id' => $this->memberType->id,
                'code' => $this->memberType->code,
                'name' => $this->memberType->name,
            ] : null,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
