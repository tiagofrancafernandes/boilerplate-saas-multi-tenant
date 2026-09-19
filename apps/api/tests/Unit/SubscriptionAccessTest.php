<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Domain\SubscriptionAccessService;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    public function testActiveTrialAllowsWritingAndDoesNotWarn(): void
    {
        $tenant = new Tenant([
            'id' => 'tenant-trial',
            'status' => TenantStatus::TRIALING,
            'trial_ends_at' => now()->utc()->addDays(7),
        ]);

        $this->assertTrue(SubscriptionAccessService::canPerformWrite($tenant));
        $this->assertFalse(SubscriptionAccessService::shouldWarnSubscription($tenant));
    }

    public function testExpiredTrialBlocksWriting(): void
    {
        $tenant = new Tenant([
            'id' => 'tenant-expired-trial',
            'status' => TenantStatus::TRIALING,
            'trial_ends_at' => now()->utc()->subDay(),
            'paid_until' => null,
        ]);

        $this->assertFalse(SubscriptionAccessService::canPerformWrite($tenant));
        $this->assertFalse(SubscriptionAccessService::shouldWarnSubscription($tenant));
    }

    public function testActivePaidSubscriptionAllowsWriting(): void
    {
        $tenant = new Tenant([
            'id' => 'tenant-paid',
            'status' => TenantStatus::ACTIVE,
            'paid_until' => now()->utc()->addMonth(),
        ]);

        $this->assertTrue(SubscriptionAccessService::canPerformWrite($tenant));
        $this->assertFalse(SubscriptionAccessService::shouldWarnSubscription($tenant));
    }

    public function testPaymentFailureWithinGracePeriodAllowsWritingAndWarns(): void
    {
        $tenant = new Tenant([
            'id' => 'tenant-grace',
            'status' => TenantStatus::PAST_DUE,
            'paid_until' => now()->utc()->subHour(),
            'payment_failed_at' => now()->utc()->subDay(),
        ]);

        $this->assertTrue(SubscriptionAccessService::canPerformWrite($tenant));
        $this->assertTrue(SubscriptionAccessService::shouldWarnSubscription($tenant));
    }

    public function testPaymentFailureBeyondGracePeriodBlocksWriting(): void
    {
        $tenant = new Tenant([
            'id' => 'tenant-grace-expired',
            'status' => TenantStatus::PAST_DUE,
            'paid_until' => now()->utc()->subDays(5),
            'payment_failed_at' => now()->utc()->subDays(5),
        ]);

        $this->assertFalse(SubscriptionAccessService::canPerformWrite($tenant));
        $this->assertFalse(SubscriptionAccessService::shouldWarnSubscription($tenant));
    }
}
