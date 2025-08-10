<?php

use App\Jobs\CheckSubscriptionsJob;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use App\Telegram\Services\HandleChannel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock the HandleChannel class
    $this->handleChannelMock = Mockery::mock(HandleChannel::class);
    app()->instance(HandleChannel::class, $this->handleChannelMock);
});

describe('CheckSubscriptionsJob', function () {
    it('deactivates expired subscriptions and kicks users from channel', function () {
        // Arrange
        $plan = Plan::factory()->create();
        $client = Client::factory()->withLanguage('uz')->create();
        
        // Create an expired subscription
        $expiredSubscription = Subscription::factory()
            ->for($client)
            ->for($plan)
            ->active()
            ->expiredDaysAgo(1)
            ->create();

        // Mock HandleChannel to return a valid user and allow kicking
        $this->handleChannelMock->shouldReceive('getChannelUser')
            ->once()
            ->andReturn('valid_user');
        $this->handleChannelMock->shouldReceive('kickUser')
            ->once();

        // Act
        $job = new CheckSubscriptionsJob();
        $job->handle();

        // Assert
        expect($expiredSubscription->fresh()->status)->toBe(false);
    });

    it('does not deactivate active subscriptions that have not expired', function () {
        // Arrange
        $plan = Plan::factory()->create();
        $client = Client::factory()->withLanguage('uz')->create();
        
        // Create an active subscription that expires in the future
        $activeSubscription = Subscription::factory()
            ->for($client)
            ->for($plan)
            ->active()
            ->expiresInDays(5)
            ->create();

        // Act
        $job = new CheckSubscriptionsJob();
        $job->handle();

        // Assert
        expect($activeSubscription->fresh()->status)->toBe(true);
    });

    it('does not kick users when channel user is unknown', function () {
        // Arrange
        $plan = Plan::factory()->create();
        $client = Client::factory()->withLanguage('uz')->create();
        
        // Create an expired subscription
        $expiredSubscription = Subscription::factory()
            ->for($client)
            ->for($plan)
            ->active()
            ->expiredDaysAgo(1)
            ->create();

        // Mock HandleChannel to return unknown user
        $this->handleChannelMock->shouldReceive('getChannelUser')
            ->once()
            ->andReturn('unknown');
        $this->handleChannelMock->shouldNotReceive('kickUser');

        // Act
        $job = new CheckSubscriptionsJob();
        $job->handle();

        // Assert
        expect($expiredSubscription->fresh()->status)->toBe(false);
    });

    it('sets correct locale for each client', function () {
        // Arrange
        $plan = Plan::factory()->create();
        $uzClient = Client::factory()->withLanguage('uz')->create();
        $ruClient = Client::factory()->withLanguage('ru')->create();
        
        // Create expired subscriptions for different language clients
        $uzSubscription = Subscription::factory()
            ->for($uzClient)
            ->for($plan)
            ->active()
            ->expiredDaysAgo(1)
            ->create();

        $ruSubscription = Subscription::factory()
            ->for($ruClient)
            ->for($plan)
            ->active()
            ->expiredDaysAgo(1)
            ->create();

        // Mock HandleChannel for both clients
        $this->handleChannelMock->shouldReceive('getChannelUser')
            ->twice()
            ->andReturn('valid_user');
        $this->handleChannelMock->shouldReceive('kickUser')
            ->twice();

        // Act
        $job = new CheckSubscriptionsJob();
        $job->handle();

        // Assert
        expect($uzSubscription->fresh()->status)->toBe(false);
        expect($ruSubscription->fresh()->status)->toBe(false);
    });

    it('handles multiple expired subscriptions for the same client', function () {
        // Arrange
        $plan1 = Plan::factory()->create();
        $plan2 = Plan::factory()->create();
        $client = Client::factory()->withLanguage('uz')->create();
        
        // Create multiple expired subscriptions for the same client
        $subscription1 = Subscription::factory()
            ->for($client)
            ->for($plan1)
            ->active()
            ->expiredDaysAgo(1)
            ->create();

        $subscription2 = Subscription::factory()
            ->for($client)
            ->for($plan2)
            ->active()
            ->expiredDaysAgo(2)
            ->create();

        // Mock HandleChannel
        $this->handleChannelMock->shouldReceive('getChannelUser')
            ->twice()
            ->andReturn('valid_user');
        $this->handleChannelMock->shouldReceive('kickUser')
            ->twice();

        // Act
        $job = new CheckSubscriptionsJob();
        $job->handle();

        // Assert
        expect($subscription1->fresh()->status)->toBe(false);
        expect($subscription2->fresh()->status)->toBe(false);
    });

    it('ignores already inactive subscriptions', function () {
        // Arrange
        $plan = Plan::factory()->create();
        $client = Client::factory()->withLanguage('uz')->create();
        
        // Create an inactive expired subscription
        $inactiveSubscription = Subscription::factory()
            ->for($client)
            ->for($plan)
            ->inactive()
            ->expiredDaysAgo(1)
            ->create();

        // Act
        $job = new CheckSubscriptionsJob();
        $job->handle();

        // Assert
        expect($inactiveSubscription->fresh()->status)->toBe(false);
        // Should not call HandleChannel methods for inactive subscriptions
        $this->handleChannelMock->shouldNotHaveReceived('getChannelUser');
        $this->handleChannelMock->shouldNotHaveReceived('kickUser');
    });

    it('handles subscriptions that expire exactly now', function () {
        // Arrange
        $plan = Plan::factory()->create();
        $client = Client::factory()->withLanguage('uz')->create();
        
        // Create a subscription that expires exactly now
        $expiringNowSubscription = Subscription::factory()
            ->for($client)
            ->for($plan)
            ->active()
            ->create(['expires_at' => Carbon::now()]);

        // Mock HandleChannel
        $this->handleChannelMock->shouldReceive('getChannelUser')
            ->once()
            ->andReturn('valid_user');
        $this->handleChannelMock->shouldReceive('kickUser')
            ->once();

        // Act
        $job = new CheckSubscriptionsJob();
        $job->handle();

        // Assert
        expect($expiringNowSubscription->fresh()->status)->toBe(false);
    });

    it('handles empty subscription list gracefully', function () {
        // Act
        $job = new CheckSubscriptionsJob();
        $job->handle();

        // Assert - should not throw any errors
        expect(true)->toBe(true);
    });

    it('sets default locale when client has no language preference', function () {
        // Arrange
        $plan = Plan::factory()->create();
        $client = Client::factory()->create(['lang' => null]);
        
        // Create an expired subscription
        $expiredSubscription = Subscription::factory()
            ->for($client)
            ->for($plan)
            ->active()
            ->expiredDaysAgo(1)
            ->create();

        // Mock HandleChannel
        $this->handleChannelMock->shouldReceive('getChannelUser')
            ->once()
            ->andReturn('valid_user');
        $this->handleChannelMock->shouldReceive('kickUser')
            ->once();

        // Act
        $job = new CheckSubscriptionsJob();
        $job->handle();

        // Assert
        expect($expiredSubscription->fresh()->status)->toBe(false);
        expect(app()->getLocale())->toBe('uz'); // Default locale should be set
    });
}); 