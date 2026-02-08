<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_subscription_plan_with_valid_data(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => $plan->name,
        ]);

        $this->assertIsString($plan->name);
        $this->assertIsInt($plan->duration_days);
        $this->assertIsNumeric($plan->price);
        $this->assertIsArray($plan->features);
        $this->assertTrue($plan->is_active);
    }

    public function test_factory_creates_monthly_plan(): void
    {
        $plan = SubscriptionPlan::factory()->monthly()->create();

        $this->assertEquals('Monthly', $plan->name);
        $this->assertEquals(30, $plan->duration_days);
        $this->assertEquals(9.99, $plan->price);
        $this->assertIsArray($plan->features);
        $this->assertTrue($plan->is_active);
    }

    public function test_factory_creates_quarterly_plan(): void
    {
        $plan = SubscriptionPlan::factory()->quarterly()->create();

        $this->assertEquals('Quarterly', $plan->name);
        $this->assertEquals(90, $plan->duration_days);
        $this->assertEquals(24.99, $plan->price);
        $this->assertIsArray($plan->features);
        $this->assertTrue($plan->is_active);
    }

    public function test_factory_creates_yearly_plan(): void
    {
        $plan = SubscriptionPlan::factory()->yearly()->create();

        $this->assertEquals('Yearly', $plan->name);
        $this->assertEquals(365, $plan->duration_days);
        $this->assertEquals(79.99, $plan->price);
        $this->assertIsArray($plan->features);
        $this->assertTrue($plan->is_active);
    }

    public function test_factory_creates_inactive_plan(): void
    {
        $plan = SubscriptionPlan::factory()->inactive()->create();

        $this->assertFalse($plan->is_active);
    }

    public function test_factory_generates_realistic_plan_names(): void
    {
        $validNames = ['Monthly', 'Quarterly', 'Semi-Annual', 'Yearly'];
        
        $plan = SubscriptionPlan::factory()->create();

        $this->assertContains($plan->name, $validNames);
    }

    public function test_factory_generates_realistic_durations(): void
    {
        $validDurations = [30, 90, 180, 365];
        
        $plan = SubscriptionPlan::factory()->create();

        $this->assertContains($plan->duration_days, $validDurations);
    }

    public function test_factory_generates_realistic_prices(): void
    {
        $validPrices = [9.99, 24.99, 44.99, 79.99];
        
        $plan = SubscriptionPlan::factory()->create();

        $this->assertContains((float)$plan->price, $validPrices);
    }

    public function test_factory_generates_features_array(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->assertIsArray($plan->features);
        $this->assertNotEmpty($plan->features);
        $this->assertContains('Access to all paid courses', $plan->features);
    }
}
