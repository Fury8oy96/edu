<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status,
            'plan_name' => $this->subscriptionPlan->name,
            'transaction_date' => $this->created_at->toISOString(),
            'subscription_period' => [
                'starts_at' => $this->submitted_at?->toISOString(),
                'expires_at' => $this->subscription_expires_at?->toISOString(),
            ],
        ];
    }
}
