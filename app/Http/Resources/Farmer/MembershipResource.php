<?php

namespace App\Http\Resources\Farmer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'farmer_id' => $this->id,
            'farmer_code' => $this->farmer_code,
            'membership_status' => $this->membership_status?->value ?? (string) $this->membership_status,
            'status' => $this->status->value,
            'member_type' => $this->memberType ? [
                'id' => $this->memberType->id,
                'code' => $this->memberType->code,
                'name' => $this->memberType->name,
            ] : null,
            'registered_at' => optional($this->registered_at)->toIso8601String(),
            'activated_at' => optional($this->activated_at)->toIso8601String(),
            'latest_renewal' => $this->whenLoaded('renewalRequests', function (): ?array {
                $renewal = $this->renewalRequests->sortByDesc('id')->first();

                if (! $renewal) {
                    return null;
                }

                return [
                    'id' => $renewal->getRouteKey(),
                    'application_no' => $renewal->application_no,
                    'year' => $renewal->year,
                    'status' => $renewal->status?->value ?? (string) $renewal->status,
                    'submitted_at' => optional($renewal->submitted_at)->toIso8601String(),
                    'reviewed_at' => optional($renewal->reviewed_at)->toIso8601String(),
                ];
            }),
        ];
    }
}
