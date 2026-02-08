<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Monthly Plan
        SubscriptionPlan::create([
            'name' => 'Monthly',
            'duration_days' => 30,
            'price' => 29.99,
            'features' => [
                'Access to all paid courses',
                'HD video quality',
                'Mobile and desktop access',
                'Course completion certificates',
                'Email support',
            ],
            'is_active' => true,
        ]);

        // Quarterly Plan (3 months)
        SubscriptionPlan::create([
            'name' => 'Quarterly',
            'duration_days' => 90,
            'price' => 79.99,
            'features' => [
                'Access to all paid courses',
                'HD video quality',
                'Mobile and desktop access',
                'Course completion certificates',
                'Priority email support',
                'Downloadable resources',
                '11% savings vs monthly',
            ],
            'is_active' => true,
        ]);

        // Yearly Plan (12 months)
        SubscriptionPlan::create([
            'name' => 'Yearly',
            'duration_days' => 365,
            'price' => 299.99,
            'features' => [
                'Access to all paid courses',
                'HD video quality',
                'Mobile and desktop access',
                'Course completion certificates',
                'Priority email support',
                'Downloadable resources',
                'Early access to new courses',
                'Exclusive webinars and workshops',
                '17% savings vs monthly',
            ],
            'is_active' => true,
        ]);

        // Lifetime Plan (optional premium tier)
        SubscriptionPlan::create([
            'name' => 'Lifetime',
            'duration_days' => 36500, // 100 years
            'price' => 999.99,
            'features' => [
                'Lifetime access to all paid courses',
                'HD video quality',
                'Mobile and desktop access',
                'Course completion certificates',
                'Priority email support',
                'Downloadable resources',
                'Early access to new courses',
                'Exclusive webinars and workshops',
                'One-on-one mentorship session',
                'VIP community access',
            ],
            'is_active' => true,
        ]);
    }
}
