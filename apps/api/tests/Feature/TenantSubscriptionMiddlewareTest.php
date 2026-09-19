<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSubscriptionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function testActiveTenantCanPerformReadAndWrite(): void
    {
        $tenant = Tenant::create([
            'id' => 'tenant-active',
            'name' => 'Active Tenant',
            'status' => TenantStatus::ACTIVE,
            'paid_until' => now()->utc()->addMonth(),
        ]);

        tenancy()->initialize($tenant);

        $readResponse = $this->getJson('/api/tenant/profile');
        $readResponse->assertStatus(200);

        $writeResponse = $this->postJson('/api/tenant/resources', [
            'name' => 'Test Item',
        ]);
        $writeResponse->assertStatus(201);
        $writeResponse->assertHeaderMissing('X-Subscription-Warning');

        tenancy()->end();
    }

    public function testGracePeriodTenantCanWriteAndReceivesWarningHeader(): void
    {
        $tenant = Tenant::create([
            'id' => 'tenant-grace',
            'name' => 'Grace Tenant',
            'status' => TenantStatus::PAST_DUE,
            'paid_until' => now()->utc()->subHour(),
            'payment_failed_at' => now()->utc()->subDay(),
        ]);

        tenancy()->initialize($tenant);

        $readResponse = $this->getJson('/api/tenant/profile');
        $readResponse->assertStatus(200);
        $readResponse->assertHeader('X-Subscription-Warning');

        $writeResponse = $this->postJson('/api/tenant/resources', [
            'name' => 'Grace Item',
        ]);
        $writeResponse->assertStatus(201);
        $writeResponse->assertHeader('X-Subscription-Warning');

        tenancy()->end();
    }

    public function testExpiredTenantAllowsReadButBlocksWriteWith403(): void
    {
        $tenant = Tenant::create([
            'id' => 'tenant-expired',
            'name' => 'Expired Tenant',
            'status' => TenantStatus::PAST_DUE,
            'paid_until' => now()->utc()->subDays(10),
            'payment_failed_at' => now()->utc()->subDays(10),
        ]);

        tenancy()->initialize($tenant);

        $readResponse = $this->getJson('/api/tenant/profile');
        $readResponse->assertStatus(200);

        $writeResponse = $this->postJson('/api/tenant/resources', [
            'name' => 'Blocked Item',
        ]);
        $writeResponse->assertStatus(403);
        $writeResponse->assertJsonFragment([
            'error' => 'SUBSCRIPTION_READ_ONLY',
        ]);

        tenancy()->end();
    }
}
