<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Admin;
use App\Models\Students;
use App\Exceptions\PaymentNotPendingException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminPaymentService
{
    public function getPendingPayments(): Collection
    {
        return Payment::where('status', 'pending')
            ->with(['student', 'subscriptionPlan'])
            ->orderBy('submitted_at', 'asc')
            ->get();
    }

    public function approvePayment(Payment $payment, Admin $admin): Payment
    {
        if (!$payment->isPending()) {
            throw new PaymentNotPendingException();
        }

        // Calculate subscription expiry date
        $expiryDate = $this->calculateExpiryDate(
            $payment->student,
            $payment->subscriptionPlan->duration_days
        );

        $payment->update([
            'status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'subscription_expires_at' => $expiryDate,
        ]);

        return $payment->fresh();
    }

    public function rejectPayment(Payment $payment, Admin $admin): Payment
    {
        if (!$payment->isPending()) {
            throw new PaymentNotPendingException();
        }

        $payment->update([
            'status' => 'rejected',
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);

        return $payment->fresh();
    }

    public function getPaymentHistory(
        ?string $status = null,
        ?int $studentId = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Payment::with(['student', 'subscriptionPlan', 'verifiedBy']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        return $query->orderBy('submitted_at', 'desc')
            ->paginate($perPage);
    }

    private function calculateExpiryDate(Students $student, int $durationDays): \DateTime
    {
        $activeSubscription = $student->getActiveSubscription();

        // If student has active subscription, extend from expiry date
        if ($activeSubscription) {
            return $activeSubscription->subscription_expires_at
                ->addDays($durationDays);
        }

        // Otherwise, start from now
        return now()->addDays($durationDays);
    }
}
