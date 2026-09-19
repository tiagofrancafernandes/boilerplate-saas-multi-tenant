<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Domain\SubscriptionCycleSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionCycleSyncTest extends TestCase
{
    use RefreshDatabase;

    public function testSyncActiveTrialTenantHappyPath(): void
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::create([
            'id' => 'trial-sync-test',
            'name' => 'Trial Sync Test',
            'status' => TenantStatus::SUSPENDED,
            'plan' => SubscriptionPlan::STARTER,
            'trial_ends_at' => now()->addDays(7),
            'paid_until' => null,
            'payment_failed_at' => now()->subDays(5),
        ]);

        $result = SubscriptionCycleSyncService::syncTenant($tenant);

        $this->assertSame('trial-sync-test', $result['tenant_id']);
        $this->assertSame(TenantStatus::TRIALING->value, $result['current_status']);
        $this->assertNull($result['payment_failed_at']);
        $this->assertTrue($result['changed']);

        $tenant->refresh();
        $this->assertSame(TenantStatus::TRIALING, $tenant->status);
    }

    public function testSyncActivePaidTenantHappyPath(): void
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::create([
            'id' => 'paid-sync-test',
            'name' => 'Paid Sync Test',
            'status' => TenantStatus::ACTIVE,
            'plan' => SubscriptionPlan::PROFESSIONAL,
            'trial_ends_at' => now()->subDays(30),
            'paid_until' => now()->addDays(15),
            'payment_failed_at' => null,
        ]);

        $result = SubscriptionCycleSyncService::syncTenant($tenant);

        $this->assertSame('paid-sync-test', $result['tenant_id']);
        $this->assertSame(TenantStatus::ACTIVE->value, $result['current_status']);
        $this->assertFalse($result['changed']);
    }

    public function testSyncLapsedSubscriptionInGracePeriodHappyPath(): void
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::create([
            'id' => 'grace-sync-test',
            'name' => 'Grace Sync Test',
            'status' => TenantStatus::ACTIVE,
            'plan' => SubscriptionPlan::STARTER,
            'trial_ends_at' => now()->subDays(30),
            'paid_until' => now()->subDay(),
            'payment_failed_at' => now()->subDay(),
        ]);

        $result = SubscriptionCycleSyncService::syncTenant($tenant);

        $this->assertSame('grace-sync-test', $result['tenant_id']);
        $this->assertSame(TenantStatus::PAST_DUE->value, $result['current_status']);
        $this->assertTrue($result['changed']);

        $tenant->refresh();
        $this->assertSame(TenantStatus::PAST_DUE, $tenant->status);
    }

    public function testSyncExpiredSubscriptionBeyondGracePeriodHappyPath(): void
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::create([
            'id' => 'expired-sync-test',
            'name' => 'Expired Sync Test',
            'status' => TenantStatus::ACTIVE,
            'plan' => SubscriptionPlan::STARTER,
            'trial_ends_at' => now()->subDays(30),
            'paid_until' => now()->subDays(10),
            'payment_failed_at' => now()->subDays(10),
        ]);

        $result = SubscriptionCycleSyncService::syncTenant($tenant);

        $this->assertSame('expired-sync-test', $result['tenant_id']);
        $this->assertSame(TenantStatus::SUSPENDED->value, $result['current_status']);
        $this->assertTrue($result['changed']);

        $tenant->refresh();
        $this->assertSame(TenantStatus::SUSPENDED, $tenant->status);
    }

    public function testSyncCanceledTenantIsUntouchedHappyPath(): void
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::create([
            'id' => 'canceled-sync-test',
            'name' => 'Canceled Sync Test',
            'status' => TenantStatus::CANCELED,
            'plan' => SubscriptionPlan::ENTERPRISE,
            'trial_ends_at' => now()->subDays(50),
            'paid_until' => null,
            'payment_failed_at' => null,
        ]);

        $result = SubscriptionCycleSyncService::syncTenant($tenant);

        $this->assertSame(TenantStatus::CANCELED->value, $result['current_status']);
        $this->assertFalse($result['changed']);
    }

    public function testCommandCheckSubscriptionCyclesSyncAllHappyPath(): void
    {
        Tenant::create([
            'id' => 'cmd-tenant-1',
            'name' => 'CMD Tenant 1',
            'status' => TenantStatus::ACTIVE,
            'plan' => SubscriptionPlan::STARTER,
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->artisan('subscriptions:check-cycles')
            ->expectsOutputToContain('Starting subscription cycle scan for all tenants...')
            ->expectsOutputToContain('Subscription cycle scan completed successfully.')
            ->assertExitCode(0);
    }

    public function testCommandCheckSubscriptionCyclesSingleTenantHappyPath(): void
    {
        Tenant::create([
            'id' => 'cmd-tenant-2',
            'name' => 'CMD Tenant 2',
            'status' => TenantStatus::ACTIVE,
            'plan' => SubscriptionPlan::STARTER,
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->artisan('subscriptions:check-cycles', ['--tenant' => 'cmd-tenant-2'])
            ->expectsOutputToContain('Synced subscription for tenant: cmd-tenant-2')
            ->assertExitCode(0);
    }

    public function testCommandCheckSubscriptionCyclesNonExistentTenantSadPath(): void
    {
        $this->artisan('subscriptions:check-cycles', ['--tenant' => 'does-not-exist'])
            ->expectsOutputToContain("Tenant with ID 'does-not-exist' not found.")
            ->assertExitCode(1);
    }
}
