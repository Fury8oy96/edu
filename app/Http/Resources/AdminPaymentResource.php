<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminPaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student' => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'email' => $this->student->email,
            ],
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'verified_at' => $this->verified_at,
            'verified_by' => $this->when(
                $this->verifiedBy,
                fn() => [
                    'id' => $this->verifiedBy->id,
                    'name' => $this->verifiedBy->name,
                ]
            ),
            'subscription_plan' => [
                'id' => $this->subscriptionPlan->id,
                'name' => $this->subscriptionPlan->name,
                'duration_days' => $this->subscriptionPlan->duration_days,
                'price' => $this->subscriptionPlan->price,
            ],
            'subscription_expires_at' => $this->subscription_expires_at,
        ];
    }
}
