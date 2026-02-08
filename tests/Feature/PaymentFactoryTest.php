<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Payment;
use App\Models\Students;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_payment_with_default_pending_state()
    {
        $payment = Payment::factory()->create();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $payment->status);
        $this->assertNull($payment->verified_at);
        $this->assertNull($payment->verified_by);
        $this->assertNull($payment->subscription_expires_at);
        $this->assertNotNull($payment->transaction_id);
        $this->assertNotNull($payment->submitted_at);
    }

    /** @test */
    public function it_creates_payment_with_pending_state()
    {
        $payment = Payment::factory()->pending()->create();

        $this->assertEquals('pending', $payment->status);
        $this->assertNull($payment->verified_at);
        $this->assertNull($payment->verified_by);
        $this->assertNull($payment->subscription_expires_at);
    }

    /** @test */
    public function it_creates_payment_with_approved_state()
    {
        $payment = Payment::factory()->approved()->create();

        $this->assertEquals('approved', $payment->status);
        $this->assertNotNull($payment->verified_at);
        $this->assertNotNull($payment->verified_by);
        $this->assertNotNull($payment->subscription_expires_at);
        
        // Verify admin exists
        $this->assertInstanceOf(Admin::class, $payment->verifiedBy);
    }

    /** @test */
    public function it_creates_payment_with_rejected_state()
    {
        $payment = Payment::factory()->rejected()->create();

        $this->assertEquals('rejected', $payment->status);
        $this->assertNotNull($payment->verified_at);
        $this->assertNotNull($payment->verified_by);
        $this->assertNull($payment->subscription_expires_at);
        
        // Verify admin exists
        $this->assertInstanceOf(Admin::class, $payment->verifiedBy);
    }

    /** @test */
    public function it_creates_payment_for_specific_student()
    {
        $student = Students::factory()->create();
        $payment = Payment::factory()->forStudent($student)->create();

        $this->assertEquals($student->id, $payment->student_id);
        $this->assertInstanceOf(Students::class, $payment->student);
    }

    /** @test */
    public function it_creates_payment_for_specific_plan()
    {
        $plan = SubscriptionPlan::factory()->create(['price' => 49.99]);
        $payment = Payment::factory()->forPlan($plan)->create();

        $this->assertEquals($plan->id, $payment->subscription_plan_id);
        $this->assertEquals('49.99', $payment->amount);
    }

    /** @test */
    public function it_creates_payment_with_custom_amount()
    {
        $payment = Payment::factory()->withAmount(99.99)->create();

        $this->assertEquals('99.99', $payment->amount);
    }

    /** @test */
    public function it_creates_payment_with_custom_transaction_id()
    {
        $transactionId = 'CUSTOM-TXN-12345';
        $payment = Payment::factory()->withTransactionId($transactionId)->create();

        $this->assertEquals($transactionId, $payment->transaction_id);
    }

    /** @test */
    public function it_creates_payment_with_active_subscription()
    {
        $payment = Payment::factory()->activeSubscription()->create();

        $this->assertEquals('approved', $payment->status);
        $this->assertNotNull($payment->subscription_expires_at);
        $this->assertTrue($payment->subscription_expires_at->isFuture());
    }

    /** @test */
    public function it_creates_payment_with_expired_subscription()
    {
        $payment = Payment::factory()->expiredSubscription()->create();

        $this->assertEquals('approved', $payment->status);
        $this->assertNotNull($payment->subscription_expires_at);
        $this->assertTrue($payment->subscription_expires_at->isPast());
    }

    /** @test */
    public function it_generates_realistic_transaction_ids()
    {
        $transactionIds = [];
        
        for ($i = 0; $i < 10; $i++) {
            $payment = Payment::factory()->create();
            $transactionIds[] = $payment->transaction_id;
        }

        // Verify all transaction IDs are unique
        $this->assertCount(10, array_unique($transactionIds));
        
        // Verify they are not empty
        foreach ($transactionIds as $txnId) {
            $this->assertNotEmpty($txnId);
            $this->assertIsString($txnId);
        }
    }

    /** @test */
    public function it_matches_amount_to_subscription_plan_price()
    {
        $plan = SubscriptionPlan::factory()->create(['price' => 29.99]);
        $payment = Payment::factory()->forPlan($plan)->create();

        $this->assertEquals($plan->price, $payment->amount);
    }

    /** @test */
    public function it_creates_relationships_correctly()
    {
        $student = Students::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        
        $payment = Payment::factory()
            ->forStudent($student)
            ->forPlan($plan)
            ->approved()
            ->create();

        // Test relationships
        $this->assertInstanceOf(Students::class, $payment->student);
        $this->assertInstanceOf(SubscriptionPlan::class, $payment->subscriptionPlan);
        $this->assertInstanceOf(Admin::class, $payment->verifiedBy);
        
        $this->assertEquals($student->id, $payment->student->id);
        $this->assertEquals($plan->id, $payment->subscriptionPlan->id);
    }
}
