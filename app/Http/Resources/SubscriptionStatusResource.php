<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [
                'has_active_subscription' => false,
                'message' => 'No active subscription',
            ];
        }
        
        $daysRemaining = now()->diffInDays($this->subscription_expires_at, false);
        
        return [
            'has_active_subscription' => true,
            'plan_name' => $this->plan_name,
            'expires_at' => $this->subscription_expires_at->toISOString(),
            'days_remaining' => $daysRemaining,
            'is_renewable' => $daysRemaining <= 7,
        ];
    }
}
