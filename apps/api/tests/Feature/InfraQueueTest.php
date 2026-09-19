<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InfraQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app_rules.infra.queue_enabled' => true,
            'app_rules.infra.auth_token' => 'valid-queue-key',
            'app_rules.infra.header_name' => 'X-Infra-Key',
            'app_rules.infra.role' => 'super-admin',
        ]);
    }

    public function testQueueWorkerHappyPathExecutesWithValidHeaderKey(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:work', \Mockery::on(fn (array $params) => ($params['--stop-when-empty'] ?? false) === true
                    && ($params['--max-jobs'] ?? 0) === 1))
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn("Processed 0 jobs\n");

        $response = $this->postJson('/api/infra/queue', [], [
            'X-Infra-Key' => 'valid-queue-key',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Queue worker executed successfully.',
                'data' => [
                    'success' => true,
                    'exit_code' => 0,
                    'output' => 'Processed 0 jobs',
                ],
            ]);
    }

    public function testQueueWorkerHappyPathViaShortInfraPrefix(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:work', \Mockery::type('array'))
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Done');

        $response = $this->postJson('/infra/queue', [], [
            'X-Infra-Key' => 'valid-queue-key',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function testQueueWorkerHappyPathWithCustomOptions(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:work', \Mockery::on(fn (array $params) => ($params['connection'] ?? null) === 'redis'
                    && ($params['--queue'] ?? null) === 'high-priority'
                    && ($params['--max-jobs'] ?? 0) === 5
                    && ($params['--stop-when-empty'] ?? false) === true))
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Processed 5 jobs');

        $response = $this->postJson('/api/infra/queue', [
            'connection' => 'redis',
            'queue' => 'high-priority',
            'max_jobs' => 5,
            'stop_when_empty' => true,
        ], [
            'X-Infra-Key' => 'valid-queue-key',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function testQueueWorkerHappyPathWithSuperAdminToken(): void
    {
        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole($role);

        Sanctum::actingAs($admin);

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:work', \Mockery::type('array'))
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('');

        $response = $this->postJson('/api/infra/queue');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function testQueueWorkerHappyPathDispatchesWebhooks(): void
    {
        Http::fake([
            'https://webhook.site/queue-success' => Http::response('OK', 200),
            'https://webhook.site/queue-final' => Http::response('OK', 200),
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:work', \Mockery::type('array'))
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Processed 1 job');

        $response = $this->postJson('/api/infra/queue', [
            'webhooks' => [
                'success' => [
                    'url' => 'https://webhook.site/queue-success',
                    'method' => 'POST',
                ],
                'final' => [
                    'url' => 'https://webhook.site/queue-final',
                    'method' => 'POST',
                ],
            ],
        ], [
            'X-Infra-Key' => 'valid-queue-key',
        ]);

        $response->assertStatus(200);

        Http::assertSent(fn ($request) => $request->url() === 'https://webhook.site/queue-success');

        Http::assertSent(fn ($request) => $request->url() === 'https://webhook.site/queue-final');
    }

    public function testQueueWorkerSadPathReturns404WhenUnauthenticated(): void
    {
        $response = $this->postJson('/api/infra/queue');

        $response->assertStatus(404);
    }

    public function testQueueWorkerSadPathReturns404WhenKeyIsInvalid(): void
    {
        $response = $this->postJson('/api/infra/queue', [], [
            'X-Infra-Key' => 'wrong-key',
        ]);

        $response->assertStatus(404);
    }

    public function testQueueWorkerSadPathReturns404WhenQueueDisabled(): void
    {
        config(['app_rules.infra.queue_enabled' => false]);

        $response = $this->postJson('/api/infra/queue', [], [
            'X-Infra-Key' => 'valid-queue-key',
        ]);

        $response->assertStatus(404);
    }

    public function testQueueWorkerSadPathReturns404WhenUserLacksSuperAdmin(): void
    {
        /** @var User $regularUser */
        $regularUser = User::factory()->create();
        Sanctum::actingAs($regularUser);

        $response = $this->postJson('/api/infra/queue');

        $response->assertStatus(404);
    }
}
