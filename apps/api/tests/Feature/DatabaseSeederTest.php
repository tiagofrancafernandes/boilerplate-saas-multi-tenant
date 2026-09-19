<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function testDatabaseSeederHappyPathCreatesAdminAndTenants(): void
    {
        $this->seed(DatabaseSeeder::class);

        /** @var User|null $admin */
        $admin = User::where('email', 'admin@mail.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('power@123', (string) $admin->password));
        $this->assertTrue($admin->hasRole('super-admin'));
        $this->assertTrue($admin->can('run-scheduler'));

        $preferences = $admin->getPreferencesWithDefaults();
        $this->assertSame('pt_BR', $preferences->get('locale'));
        $this->assertSame('UTC', $preferences->get('timezone'));
        $this->assertSame('dark', $preferences->get('color_scheme'));

        /** @var Tenant|null $demoTenant */
        $demoTenant = Tenant::find('demo_tenant');
        $this->assertNotNull($demoTenant);
        $this->assertTrue($demoTenant->isInTrial());
        $this->assertTrue($demoTenant->canWrite());

        /** @var Tenant|null $paidTenant */
        $paidTenant = Tenant::find('paid_tenant');
        $this->assertNotNull($paidTenant);
        $this->assertTrue($paidTenant->isPaidActive());
        $this->assertTrue($paidTenant->canWrite());

        /** @var Tenant|null $pendingTenant */
        $pendingTenant = Tenant::find('pending_tenant');
        $this->assertNotNull($pendingTenant);
        $this->assertTrue($pendingTenant->isInGracePeriod());
        $this->assertTrue($pendingTenant->canWrite());

        /** @var Tenant|null $expiredTenant */
        $expiredTenant = Tenant::find('expired_tenant');
        $this->assertNotNull($expiredTenant);
        $this->assertFalse($expiredTenant->isInTrial());
        $this->assertFalse($expiredTenant->isPaidActive());
        $this->assertFalse($expiredTenant->isInGracePeriod());
        $this->assertFalse($expiredTenant->canWrite());

        // Verify Plans Seeded
        $this->assertSame(3, \App\Models\Plan::count());
        $starterPlan = \App\Models\Plan::where('slug', \App\Enums\SubscriptionPlan::STARTER)->first();
        $this->assertNotNull($starterPlan);
        $this->assertSame('Starter', $starterPlan->name);
        $this->assertSame(4900, $starterPlan->price_cents);
    }

    public function testDatabaseSeederHappyPathIsIdempotentWithUpdateOrCreate(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@mail.com')->count());
        $this->assertSame(1, User::where('email', 'demo@mail.com')->count());
        $this->assertSame(1, User::where('email', 'paid@mail.com')->count());
        $this->assertSame(1, User::where('email', 'pending@mail.com')->count());
        $this->assertSame(1, User::where('email', 'expired@mail.com')->count());

        $this->assertSame(1, Tenant::where('id', 'demo_tenant')->count());
        $this->assertSame(1, Tenant::where('id', 'paid_tenant')->count());
        $this->assertSame(1, Tenant::where('id', 'pending_tenant')->count());
        $this->assertSame(1, Tenant::where('id', 'expired_tenant')->count());

        $this->assertSame(3, \App\Models\Plan::count());
    }

    public function testTenantSeederSkipsDemoUsersInProductionWithoutFlagSadPath(): void
    {
        config(['app.env' => 'production']);
        config(['app_rules.seed_demo_users' => false]);

        $this->seed(\Database\Seeders\TenantSeeder::class);

        $this->assertSame(0, Tenant::count());
        $this->assertSame(0, User::where('email', 'demo@mail.com')->count());
    }

    public function testTenantSeederAllowsDemoUsersInProductionWithFlagHappyPath(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        config(['app.env' => 'production']);
        config(['app_rules.seed_demo_users' => true]);

        $this->seed(\Database\Seeders\TenantSeeder::class);

        $this->assertSame(4, Tenant::count());
        $this->assertSame(1, User::where('email', 'demo@mail.com')->count());
    }
}
