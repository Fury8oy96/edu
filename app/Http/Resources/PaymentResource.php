<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'verified_at' => $this->verified_at,
            'subscription_plan' => [
                'id' => $this->subscriptionPlan->id,
                'name' => $this->subscriptionPlan->name,
                'duration_days' => $this->subscriptionPlan->duration_days,
            ],
        ];
    }
}
