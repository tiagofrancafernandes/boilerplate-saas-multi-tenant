<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InfraSchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app_rules.infra.scheduler_enabled' => true,
            'app_rules.infra.scheduler_auth_token' => 'secure-test-token-12345',
            'app_rules.infra.scheduler_header_name' => 'X-Infra-Key',
            'app_rules.infra.scheduler_role' => 'super-admin',
            'app_rules.infra.scheduler_permission' => 'run-scheduler',
        ]);
    }

    public function testSchedulerHappyPathExecutesWithValidHeaderKeyViaPost(): void
    {
        $response = $this->postJson('/api/infra/scheduler', [], [
            'X-Infra-Key' => 'secure-test-token-12345',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'success',
                'exit_code',
                'output',
                'executed_at',
            ],
        ]);
    }

    public function testSchedulerHappyPathExecutesWithValidHeaderKeyViaGet(): void
    {
        $response = $this->getJson('/api/infra/scheduler', [
            'X-Infra-Key' => 'secure-test-token-12345',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function testSchedulerHappyPathExecutesViaShortInfraPrefix(): void
    {
        $response = $this->postJson('/infra/scheduler', [], [
            'X-Infra-Key' => 'secure-test-token-12345',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function testSchedulerHappyPathExecutesWithStaticKeyAsBearerToken(): void
    {
        $response = $this->postJson('/api/infra/scheduler', [], [
            'Authorization' => 'Bearer secure-test-token-12345',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function testSchedulerHappyPathExecutesWithSuperAdminUserToken(): void
    {
        config(['app_rules.infra.scheduler_auth_token' => 'some-other-token']);

        Role::findOrCreate('super-admin');
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        Sanctum::actingAs($superAdmin);

        $response = $this->postJson('/api/infra/scheduler');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function testSchedulerHappyPathExecutesWithUserHavingSchedulerPermission(): void
    {
        config(['app_rules.infra.scheduler_auth_token' => 'some-other-token']);

        Permission::findOrCreate('run-scheduler');
        $userWithPermission = User::factory()->create();
        $userWithPermission->givePermissionTo('run-scheduler');

        Sanctum::actingAs($userWithPermission);

        $response = $this->postJson('/api/infra/scheduler');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function testSchedulerSadPathReturns404WhenSchedulerIsDisabled(): void
    {
        config(['app_rules.infra.scheduler_enabled' => false]);

        $response = $this->postJson('/api/infra/scheduler', [], [
            'X-Infra-Key' => 'secure-test-token-12345',
        ]);

        $response->assertStatus(404);
    }

    public function testSchedulerSadPathReturns404WhenNoCredentialsProvided(): void
    {
        $response = $this->postJson('/api/infra/scheduler');

        $response->assertStatus(404);
    }

    public function testSchedulerSadPathReturns404WhenStaticKeyIsInvalid(): void
    {
        $response = $this->postJson('/api/infra/scheduler', [], [
            'X-Infra-Key' => 'wrong-and-invalid-token',
        ]);

        $response->assertStatus(404);
    }

    public function testSchedulerSadPathReturns404WhenRegularUserLacksSuperAdminRoleOrPermission(): void
    {
        config(['app_rules.infra.scheduler_auth_token' => 'some-token']);

        $regularUser = User::factory()->create();
        Sanctum::actingAs($regularUser);

        $response = $this->postJson('/api/infra/scheduler');

        $response->assertStatus(404);
    }

    public function testSchedulerSadPathReturns404WhenStaticTokenIsNullAndNoUser(): void
    {
        config(['app_rules.infra.scheduler_auth_token' => null]);

        $response = $this->postJson('/api/infra/scheduler', [], [
            'X-Infra-Key' => 'secure-test-token-12345',
        ]);

        $response->assertStatus(404);
    }
}
