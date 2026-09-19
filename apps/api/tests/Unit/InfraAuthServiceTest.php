<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Services\Domain\InfraAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InfraAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testIsAuthorizedHappyPathWithStaticHeader(): void
    {
        config(['app_rules.infra.auth_token' => 'secret-infra-key']);
        config(['app_rules.infra.header_name' => 'X-Infra-Key']);

        $request = Request::create('/api/infra/queue', 'POST');
        $request->headers->set('X-Infra-Key', 'secret-infra-key');

        $this->assertTrue(InfraAuthService::isAuthorized($request));
    }

    public function testIsAuthorizedHappyPathWithStaticBearerToken(): void
    {
        config(['app_rules.infra.auth_token' => 'bearer-static-secret']);

        $request = Request::create('/api/infra/artisan', 'POST');
        $request->headers->set('Authorization', 'Bearer bearer-static-secret');

        $this->assertTrue(InfraAuthService::isAuthorized($request));
    }

    public function testIsAuthorizedHappyPathWithSuperAdminUser(): void
    {
        config(['app_rules.infra.auth_token' => 'another-token']);

        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole($role);

        Sanctum::actingAs($user);

        $request = Request::create('/api/infra/queue', 'POST');

        $this->assertTrue(InfraAuthService::isAuthorized($request));
    }

    public function testIsAuthorizedSadPathWithEmptyToken(): void
    {
        config(['app_rules.infra.auth_token' => null]);
        config(['app_rules.infra.scheduler_auth_token' => null]);

        $request = Request::create('/api/infra/queue', 'POST');

        $this->assertFalse(InfraAuthService::isAuthorized($request));
    }

    public function testIsAuthorizedSadPathWithWrongToken(): void
    {
        config(['app_rules.infra.auth_token' => 'correct-token']);
        config(['app_rules.infra.header_name' => 'X-Infra-Key']);

        $request = Request::create('/api/infra/queue', 'POST');
        $request->headers->set('X-Infra-Key', 'wrong-token');

        $this->assertFalse(InfraAuthService::isAuthorized($request));
    }

    public function testIsAuthorizedSadPathWithUserLackingRoleOrPermission(): void
    {
        config(['app_rules.infra.auth_token' => 'configured-token']);

        /** @var User $user */
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $request = Request::create('/api/infra/queue', 'POST');

        $this->assertFalse(InfraAuthService::isAuthorized($request));
    }

    public function testFeatureEnabledFlags(): void
    {
        config(['app_rules.infra.queue_enabled' => true]);
        config(['app_rules.infra.artisan_enabled' => false]);
        config(['app_rules.infra.scheduler_enabled' => true]);

        $this->assertTrue(InfraAuthService::isQueueEnabled());
        $this->assertFalse(InfraAuthService::isArtisanEnabled());
        $this->assertTrue(InfraAuthService::isSchedulerEnabled());
    }
}
