<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Payment;
use App\Models\Students;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     * Default state is 'pending' as per requirements.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subscriptionPlan = SubscriptionPlan::factory()->create();

        return [
            'student_id' => Students::factory(),
            'subscription_plan_id' => $subscriptionPlan->id,
            'transaction_id' => $this->generateTransactionId(),
            'amount' => $subscriptionPlan->price,
            'status' => 'pending',
            'submitted_at' => now(),
            'verified_at' => null,
            'verified_by' => null,
            'subscription_expires_at' => null,
        ];
    }

    /**
     * Generate a realistic transaction ID
     * 
     * @return string
     */
    private function generateTransactionId(): string
    {
        // Generate realistic transaction IDs in various formats
        $formats = [
            'TXN' . strtoupper(fake()->bothify('########')),
            'PAY-' . strtoupper(fake()->bothify('????-####-####')),
            strtoupper(fake()->bothify('??##??##??##')),
            'INV' . fake()->numerify('##########'),
            fake()->uuid(),
        ];

        return fake()->randomElement($formats);
    }

    /**
     * Indicate that the payment is in pending state.
     * This is the default state, but provided for explicit usage.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'verified_at' => null,
            'verified_by' => null,
            'subscription_expires_at' => null,
        ]);
    }

    /**
     * Indicate that the payment has been approved.
     * Includes verified_at, verified_by, and subscription_expires_at.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $admin = Admin::factory()->create();
            $subscriptionPlan = SubscriptionPlan::find($attributes['subscription_plan_id']) 
                ?? SubscriptionPlan::factory()->create();
            
            $verifiedAt = fake()->dateTimeBetween('-30 days', 'now');
            $subscriptionExpiresAt = (clone $verifiedAt)->modify("+{$subscriptionPlan->duration_days} days");

            return [
                'status' => 'approved',
                'verified_at' => $verifiedAt,
                'verified_by' => $admin->id,
                'subscription_expires_at' => $subscriptionExpiresAt,
            ];
        });
    }

    /**
     * Indicate that the payment has been rejected.
     * Includes verified_at and verified_by, but no subscription_expires_at.
     */
    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            $admin = Admin::factory()->create();

            return [
                'status' => 'rejected',
                'verified_at' => fake()->dateTimeBetween('-30 days', 'now'),
                'verified_by' => $admin->id,
                'subscription_expires_at' => null,
            ];
        });
    }

    /**
     * Set a specific student for the payment.
     */
    public function forStudent(Students $student): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
        ]);
    }

    /**
     * Set a specific subscription plan for the payment.
     * Also updates the amount to match the plan price.
     */
    public function forPlan(SubscriptionPlan $plan): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan_id' => $plan->id,
            'amount' => $plan->price,
        ]);
    }

    /**
     * Set a specific amount for the payment.
     * Useful for testing amount mismatch scenarios.
     */
    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Set a specific transaction ID for the payment.
     */
    public function withTransactionId(string $transactionId): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Set a specific submitted_at timestamp.
     */
    public function submittedAt($timestamp): static
    {
        return $this->state(fn (array $attributes) => [
            'submitted_at' => $timestamp,
        ]);
    }

    /**
     * Create an approved payment with active subscription (expires in future).
     */
    public function activeSubscription(): static
    {
        return $this->approved()->state(function (array $attributes) {
            $subscriptionPlan = SubscriptionPlan::find($attributes['subscription_plan_id']) 
                ?? SubscriptionPlan::factory()->create();

            return [
                'verified_at' => now()->subDays(5),
                'subscription_expires_at' => now()->addDays($subscriptionPlan->duration_days - 5),
            ];
        });
    }

    /**
     * Create an approved payment with expired subscription.
     */
    public function expiredSubscription(): static
    {
        return $this->approved()->state(fn (array $attributes) => [
            'verified_at' => now()->subDays(60),
            'subscription_expires_at' => now()->subDays(10),
        ]);
    }
}
