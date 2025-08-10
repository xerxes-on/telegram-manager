<?php

namespace Tests\Unit;

use App\Models\Card;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Telegram\Services\PaycomReceiptService;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $service;
    protected $paycomMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create plans
        Plan::create(['name' => 'one-week-free', 'price' => 0, 'days' => 7]);
        Plan::create(['name' => 'one-month', 'price' => 2000000, 'days' => 30]);
        
        // Mock Telegraph
        Telegraph::fake();
        
        // Mock PaycomReceiptService
        $this->paycomMock = Mockery::mock(PaycomReceiptService::class);
        $this->service = new SubscriptionService($this->paycomMock);
    }

    /** @test */
    public function can_create_new_subscription()
    {
        $client = Client::factory()->create();
        $plan = Plan::where('name', 'one-month')->first();
        
        $subscription = $this->service->createSubscription($client, $plan, 'receipt-123');
        
        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals($client->id, $subscription->client_id);
        $this->assertEquals($plan->id, $subscription->plan_id);
        $this->assertEquals('receipt-123', $subscription->receipt_id);
        $this->assertTrue($subscription->status);
    }

    /** @test */
    public function can_renew_subscription_with_valid_card()
    {
        $client = Client::factory()->create();
        $card = Card::factory()->create([
            'client_id' => $client->id,
            'verified' => true,
            'token' => 'card-token-123',
        ]);
        $plan = Plan::where('name', 'one-month')->first();
        $oldSubscription = Subscription::create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => true,
            'receipt_id' => 'old-receipt',
            'expires_at' => now()->addDays(2),
        ]);

        // Mock successful payment
        $this->paycomMock->shouldReceive('createReceipt')
            ->with('card-token-123', $plan->price)
            ->once()
            ->andReturn(['receipt' => ['_id' => 'new-receipt-id']]);
        
        $this->paycomMock->shouldReceive('payReceipt')
            ->with('new-receipt-id')
            ->once()
            ->andReturn(['receipt' => ['state' => 4, 'transaction' => 'trans-123']]);

        $newSubscription = $this->service->renewSubscription($oldSubscription, $card);

        $this->assertNotNull($newSubscription);
        $this->assertTrue($newSubscription->is_renewal);
        $this->assertEquals($oldSubscription->id, $newSubscription->previous_subscription_id);
        $this->assertEquals('new-receipt-id', $newSubscription->receipt_id);
        
        // Check old subscription is deactivated
        $oldSubscription->refresh();
        $this->assertFalse($oldSubscription->status);
    }

    /** @test */
    public function throws_exception_when_renewal_not_allowed()
    {
        $client = Client::factory()->create();
        $card = Card::factory()->create(['client_id' => $client->id]);
        $plan = Plan::where('name', 'one-month')->first();
        $subscription = Subscription::create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => true,
            'receipt_id' => 'test-receipt',
            'expires_at' => now()->addDays(10), // Too early to renew
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Subscription cannot be renewed yet');

        $this->service->renewSubscription($subscription, $card);
    }

    /** @test */
    public function can_change_to_free_plan()
    {
        $client = Client::factory()->create();
        $paidPlan = Plan::where('name', 'one-month')->first();
        $freePlan = Plan::where('name', 'one-week-free')->first();
        
        $oldSubscription = Subscription::create([
            'client_id' => $client->id,
            'plan_id' => $paidPlan->id,
            'status' => true,
            'receipt_id' => 'paid-receipt',
            'expires_at' => now()->addDays(2),
        ]);

        $newSubscription = $this->service->changePlan($oldSubscription, $freePlan);

        $this->assertNotNull($newSubscription);
        $this->assertEquals($freePlan->id, $newSubscription->plan_id);
        $this->assertStringStartsWith('FREE-', $newSubscription->receipt_id);
        $this->assertFalse($newSubscription->is_renewal);
        
        // Check old subscription is deactivated
        $oldSubscription->refresh();
        $this->assertFalse($oldSubscription->status);
    }

    /** @test */
    public function can_change_to_paid_plan_with_payment()
    {
        $client = Client::factory()->create();
        $card = Card::factory()->create([
            'client_id' => $client->id,
            'verified' => true,
            'token' => 'card-token-456',
        ]);
        $oldPlan = Plan::where('name', 'one-week-free')->first();
        $newPlan = Plan::where('name', 'one-month')->first();
        
        $oldSubscription = Subscription::create([
            'client_id' => $client->id,
            'plan_id' => $oldPlan->id,
            'status' => true,
            'receipt_id' => 'free-receipt',
            'expires_at' => now()->addDays(5),
        ]);

        // Mock successful payment
        $this->paycomMock->shouldReceive('createReceipt')
            ->with('card-token-456', $newPlan->price)
            ->once()
            ->andReturn(['receipt' => ['_id' => 'paid-receipt-id']]);
        
        $this->paycomMock->shouldReceive('payReceipt')
            ->with('paid-receipt-id')
            ->once()
            ->andReturn(['receipt' => ['state' => 4, 'transaction' => 'trans-456']]);

        $newSubscription = $this->service->changePlan($oldSubscription, $newPlan, $card);

        $this->assertNotNull($newSubscription);
        $this->assertEquals($newPlan->id, $newSubscription->plan_id);
        $this->assertEquals('paid-receipt-id', $newSubscription->receipt_id);
        $this->assertFalse($newSubscription->is_renewal);
        
        // Check expiry date extends from old subscription
        $expectedExpiry = $oldSubscription->expires_at->copy()->addDays($newPlan->days);
        $this->assertTrue($newSubscription->expires_at->eq($expectedExpiry));
    }

    /** @test */
    public function throws_exception_when_payment_fails()
    {
        $client = Client::factory()->create();
        $card = Card::factory()->create([
            'client_id' => $client->id,
            'verified' => true,
            'token' => 'card-token-fail',
        ]);
        $plan = Plan::where('name', 'one-month')->first();
        $subscription = Subscription::create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => true,
            'receipt_id' => 'test-receipt',
            'expires_at' => now()->addDays(2),
        ]);

        // Mock failed payment
        $this->paycomMock->shouldReceive('createReceipt')
            ->once()
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create receipt');

        $this->service->renewSubscription($subscription, $card);
    }

    /** @test */
    public function creates_subscription_transaction_on_successful_renewal()
    {
        $client = Client::factory()->create();
        $card = Card::factory()->create([
            'client_id' => $client->id,
            'verified' => true,
            'token' => 'card-token-789',
        ]);
        $plan = Plan::where('name', 'one-month')->first();
        $subscription = Subscription::create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => true,
            'receipt_id' => 'old-receipt',
            'expires_at' => now()->addDays(2),
        ]);

        // Mock successful payment
        $this->paycomMock->shouldReceive('createReceipt')
            ->once()
            ->andReturn(['receipt' => ['_id' => 'trans-receipt-id']]);
        
        $this->paycomMock->shouldReceive('payReceipt')
            ->once()
            ->andReturn(['receipt' => ['state' => 4, 'transaction' => 'trans-789']]);

        $newSubscription = $this->service->renewSubscription($subscription, $card);

        $this->assertDatabaseHas('subscription_transactions', [
            'client_id' => $client->id,
            'card_id' => $card->id,
            'subscription_id' => $newSubscription->id,
            'amount' => $plan->price,
            'receipt_id' => 'trans-receipt-id',
            'status' => 'success',
            'type' => 'renewal',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}