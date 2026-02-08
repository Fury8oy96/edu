<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Define realistic subscription plan options
        $plans = [
            [
                'name' => 'Monthly',
                'duration_days' => 30,
                'price' => 9.99,
                'features' => [
                    'Access to all paid courses',
                    'Download course materials',
                    'Certificate of completion',
                    'Email support',
                ],
            ],
            [
                'name' => 'Quarterly',
                'duration_days' => 90,
                'price' => 24.99,
                'features' => [
                    'Access to all paid courses',
                    'Download course materials',
                    'Certificate of completion',
                    'Priority email support',
                    'Access to exclusive webinars',
                ],
            ],
            [
                'name' => 'Semi-Annual',
                'duration_days' => 180,
                'price' => 44.99,
                'features' => [
                    'Access to all paid courses',
                    'Download course materials',
                    'Certificate of completion',
                    'Priority email support',
                    'Access to exclusive webinars',
                    '10% discount on future renewals',
                ],
            ],
            [
                'name' => 'Yearly',
                'duration_days' => 365,
                'price' => 79.99,
                'features' => [
                    'Access to all paid courses',
                    'Download course materials',
                    'Certificate of completion',
                    'Priority email support',
                    'Access to exclusive webinars',
                    '15% discount on future renewals',
                    'One-on-one mentorship session',
                ],
            ],
        ];

        // Randomly select a plan
        $plan = fake()->randomElement($plans);

        return [
            'name' => $plan['name'],
            'duration_days' => $plan['duration_days'],
            'price' => $plan['price'],
            'features' => $plan['features'],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the subscription plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a specific monthly plan.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Monthly',
            'duration_days' => 30,
            'price' => 9.99,
            'features' => [
                'Access to all paid courses',
                'Download course materials',
                'Certificate of completion',
                'Email support',
            ],
        ]);
    }

    /**
     * Create a specific quarterly plan.
     */
    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Quarterly',
            'duration_days' => 90,
            'price' => 24.99,
            'features' => [
                'Access to all paid courses',
                'Download course materials',
                'Certificate of completion',
                'Priority email support',
                'Access to exclusive webinars',
            ],
        ]);
    }

    /**
     * Create a specific yearly plan.
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Yearly',
            'duration_days' => 365,
            'price' => 79.99,
            'features' => [
                'Access to all paid courses',
                'Download course materials',
                'Certificate of completion',
                'Priority email support',
                'Access to exclusive webinars',
                '15% discount on future renewals',
                'One-on-one mentorship session',
            ],
        ]);
    }
}
