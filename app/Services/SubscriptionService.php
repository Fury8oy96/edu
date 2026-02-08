<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Students;
use Illuminate\Support\Collection;

class SubscriptionService
{
    public function getAvailablePlans(): Collection
    {
        return SubscriptionPlan::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();
    }

    public function getActiveSubscription(Students $student): ?array
    {
        $activePayment = $student->getActiveSubscription();

        if (!$activePayment) {
            return null;
        }

        $daysRemaining = now()->diffInDays($activePayment->subscription_expires_at, false);

        return [
            'plan_name' => $activePayment->subscriptionPlan->name,
            'expires_at' => $activePayment->subscription_expires_at,
            'days_remaining' => max(0, (int)$daysRemaining),
        ];
    }
}
