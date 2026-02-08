<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Students;
use App\Models\SubscriptionPlan;
use App\Exceptions\UnverifiedStudentException;
use App\Exceptions\InvalidSubscriptionPlanException;
use App\Exceptions\AmountMismatchException;
use App\Exceptions\DuplicatePaymentException;
use Illuminate\Support\Collection;

class PaymentService
{
    public function submitPayment(
        Students $student,
        string $transactionId,
        float $amount,
        int $subscriptionPlanId
    ): Payment {
        // Verify student email is verified
        if (!$student->isVerified()) {
            throw new UnverifiedStudentException();
        }

        // Validate subscription plan exists and is active
        $plan = SubscriptionPlan::where('id', $subscriptionPlanId)
            ->where('is_active', true)
            ->first();
        
        if (!$plan) {
            throw new InvalidSubscriptionPlanException();
        }

        // Validate amount matches plan price (within 0.01 tolerance)
        if (abs($amount - $plan->price) > 0.01) {
            throw new AmountMismatchException();
        }

        // Check for duplicate pending payment
        $hasPending = $student->payments()
            ->where('subscription_plan_id', $subscriptionPlanId)
            ->where('status', 'pending')
            ->exists();
        
        if ($hasPending) {
            throw new DuplicatePaymentException();
        }

        // Create payment submission
        return Payment::create([
            'student_id' => $student->id,
            'subscription_plan_id' => $subscriptionPlanId,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    public function getPaymentStatus(Students $student): Collection
    {
        return $student->payments()
            ->with('subscriptionPlan')
            ->orderBy('submitted_at', 'desc')
            ->get();
    }

    public function getPaymentHistory(Students $student): Collection
    {
        return $student->payments()
            ->with(['subscriptionPlan', 'verifiedBy'])
            ->orderBy('submitted_at', 'desc')
            ->get();
    }
}
