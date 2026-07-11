<?php

namespace App\Http\Resources\Farmer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InquiryThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge((new InquiryResource($this->resource))->toArray($request), [
            'unread_staff_reply_count' => (int) ($this->unread_staff_reply_count ?? 0),
            'responses' => QueryResponseResource::collection($this->whenLoaded('responses')),
        ]);
    }
}
