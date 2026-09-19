<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use Tests\TestCase;

class TenantModelTest extends TestCase
{
    public function testCastsAttributesToBackedEnumsAndDates(): void
    {
        $tenant = new Tenant([
            'id' => 'tenant-enum-test',
            'status' => 'active',
            'plan' => 'professional',
            'paid_until' => '2026-12-31 23:59:59',
            'trial_ends_at' => '2026-06-30 23:59:59',
            'payment_failed_at' => '2026-07-01 10:00:00',
        ]);

        $this->assertInstanceOf(TenantStatus::class, $tenant->status);
        $this->assertSame(TenantStatus::ACTIVE, $tenant->status);
        $this->assertInstanceOf(SubscriptionPlan::class, $tenant->plan);
        $this->assertSame(SubscriptionPlan::PROFESSIONAL, $tenant->plan);
        $this->assertNotNull($tenant->paid_until);
        $this->assertNotNull($tenant->trial_ends_at);
        $this->assertNotNull($tenant->payment_failed_at);
    }

    public function testIsInTrialHappyPathReturnsTrueWhenTrialDateInFuture(): void
    {
        $tenant = new Tenant([
            'id' => 'tenant-trial-happy',
            'trial_ends_at' => now()->utc()->addDays(5),
        ]);

        $this->assertTrue($tenant->isInTrial());
    }

    public function testIsInTrialSadPathReturnsFalseWhenTrialNullOrExpired(): void
    {
        $nullTrialTenant = new Tenant([
            'id' => 'tenant-null-trial',
            'trial_ends_at' => null,
        ]);

        $expiredTrialTenant = new Tenant([
            'id' => 'tenant-expired-trial',
            'trial_ends_at' => now()->utc()->subMinute(),
        ]);

        $this->assertFalse($nullTrialTenant->isInTrial());
        $this->assertFalse($expiredTrialTenant->isInTrial());
    }

    public function testIsPaidActiveHappyPathReturnsTrueWhenPaidUntilInFuture(): void
    {
        $tenant = new Tenant([
            'id' => 'tenant-paid-happy',
            'paid_until' => now()->utc()->addMonth(),
        ]);

        $this->assertTrue($tenant->isPaidActive());
    }

    public function testIsPaidActiveSadPathReturnsFalseWhenPaidUntilNullOrExpired(): void
    {
        $nullPaidTenant = new Tenant([
            'id' => 'tenant-null-paid',
            'paid_until' => null,
        ]);

        $expiredPaidTenant = new Tenant([
            'id' => 'tenant-expired-paid',
            'paid_until' => now()->utc()->subSecond(),
        ]);

        $this->assertFalse($nullPaidTenant->isPaidActive());
        $this->assertFalse($expiredPaidTenant->isPaidActive());
    }

    public function testIsInGracePeriodHappyPathReturnsTrueWhenPaymentFailedRecently(): void
    {
        $tenant = new Tenant([
            'id' => 'tenant-grace-happy',
            'payment_failed_at' => now()->utc()->subDays(1),
        ]);

        $this->assertTrue($tenant->isInGracePeriod());
    }

    public function testIsInGracePeriodSadPathReturnsFalseWhenNullOrPastGraceDays(): void
    {
        $nullPaymentFailedTenant = new Tenant([
            'id' => 'tenant-null-failed',
            'payment_failed_at' => null,
        ]);

        $pastGraceDaysTenant = new Tenant([
            'id' => 'tenant-past-grace',
            'payment_failed_at' => now()->utc()->subDays(10),
        ]);

        $this->assertFalse($nullPaymentFailedTenant->isInGracePeriod());
        $this->assertFalse($pastGraceDaysTenant->isInGracePeriod());
    }

    public function testCanWriteSadPathReturnsFalseWhenSuspendedOrCanceled(): void
    {
        $suspendedTenant = new Tenant([
            'id' => 'tenant-suspended',
            'status' => TenantStatus::SUSPENDED,
            'paid_until' => now()->utc()->addMonth(),
        ]);

        $canceledTenant = new Tenant([
            'id' => 'tenant-canceled',
            'status' => TenantStatus::CANCELED,
            'paid_until' => now()->utc()->addMonth(),
        ]);

        $this->assertFalse($suspendedTenant->canWrite());
        $this->assertFalse($canceledTenant->canWrite());
    }

    public function testCanWriteHappyPathReturnsTrueForTrialPaidOrGrace(): void
    {
        $trialTenant = new Tenant([
            'id' => 'tenant-write-trial',
            'status' => TenantStatus::TRIALING,
            'trial_ends_at' => now()->utc()->addDays(3),
        ]);

        $paidTenant = new Tenant([
            'id' => 'tenant-write-paid',
            'status' => TenantStatus::ACTIVE,
            'paid_until' => now()->utc()->addMonth(),
        ]);

        $graceTenant = new Tenant([
            'id' => 'tenant-write-grace',
            'status' => TenantStatus::PAST_DUE,
            'payment_failed_at' => now()->utc()->subDay(),
        ]);

        $this->assertTrue($trialTenant->canWrite());
        $this->assertTrue($paidTenant->canWrite());
        $this->assertTrue($graceTenant->canWrite());
    }
}
