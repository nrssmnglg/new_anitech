<?php

namespace App\Http\Resources\Farmer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'account' => new FarmerAccountResource($this['user']),
            'profile' => new FarmerProfileResource($this['farmer']),
            'stats' => $this['stats'],
            'action_items' => $this['action_items'],
            'priority_reminders' => $this['priority_reminders'],
            'activity_summary' => $this['activity_summary'],
            'profile_completion' => $this['profile_completion'],
            'current_renewal' => $this['current_renewal'],
            'latest_advisory' => $this['latest_advisory'],
            'quick_actions' => $this['quick_actions'],
            'latest_notification' => $this['latest_notification'],
        ];
    }
}
