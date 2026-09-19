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

class InfraArtisanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app_rules.infra.artisan_enabled' => true,
            'app_rules.infra.auth_token' => 'valid-artisan-key',
            'app_rules.infra.header_name' => 'X-Infra-Key',
            'app_rules.infra.role' => 'super-admin',
        ]);
    }

    public function testArtisanHappyPathExecutesInspireCommand(): void
    {
        $response = $this->postJson('/api/infra/artisan', [
            'command' => 'inspire',
        ], [
            'X-Infra-Key' => 'valid-artisan-key',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Artisan command executed successfully.',
                'data' => [
                    'success' => true,
                    'exit_code' => 0,
                    'command' => 'inspire',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.output'));
    }

    public function testArtisanHappyPathViaShortInfraPrefix(): void
    {
        $response = $this->postJson('/infra/artisan', [
            'command' => 'inspire',
        ], [
            'X-Infra-Key' => 'valid-artisan-key',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function testArtisanHappyPathWithParametersAndWebhooks(): void
    {
        Http::fake([
            'https://webhook.site/artisan-success' => Http::response('OK', 200),
            'https://webhook.site/artisan-final' => Http::response('OK', 200),
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('subscriptions:check-cycles', ['--tenant' => 'test-tenant'])
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn("Tenant checked\n");

        $response = $this->postJson('/api/infra/artisan', [
            'command' => 'subscriptions:check-cycles',
            'parameters' => [
                '--tenant' => 'test-tenant',
            ],
            'webhooks' => [
                'success' => [
                    'url' => 'https://webhook.site/artisan-success',
                    'method' => 'POST',
                ],
                'final' => [
                    'url' => 'https://webhook.site/artisan-final',
                    'method' => 'POST',
                ],
            ],
        ], [
            'X-Infra-Key' => 'valid-artisan-key',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exit_code' => 0,
                    'output' => 'Tenant checked',
                ],
            ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://webhook.site/artisan-success');

        Http::assertSent(fn ($request) => $request->url() === 'https://webhook.site/artisan-final');
    }

    public function testArtisanHappyPathDispatchesErrorWebhookOnFailure(): void
    {
        Http::fake([
            'https://webhook.site/artisan-error' => Http::response('OK', 200),
            'https://webhook.site/artisan-final' => Http::response('OK', 200),
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('invalid:failing-command', [])
            ->andReturn(1);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Command failed');

        $response = $this->postJson('/api/infra/artisan', [
            'command' => 'invalid:failing-command',
            'webhooks' => [
                'error' => [
                    'url' => 'https://webhook.site/artisan-error',
                    'method' => 'POST',
                ],
                'final' => [
                    'url' => 'https://webhook.site/artisan-final',
                    'method' => 'POST',
                ],
            ],
        ], [
            'X-Infra-Key' => 'valid-artisan-key',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'data' => [
                    'exit_code' => 1,
                    'output' => 'Command failed',
                ],
            ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://webhook.site/artisan-error');

        Http::assertSent(fn ($request) => $request->url() === 'https://webhook.site/artisan-final');
    }

    public function testArtisanHappyPathWithSuperAdminToken(): void
    {
        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole($role);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/infra/artisan', [
            'command' => 'inspire',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function testArtisanSadPathReturns404WhenUnauthenticated(): void
    {
        $response = $this->postJson('/api/infra/artisan', [
            'command' => 'inspire',
        ]);

        $response->assertStatus(404);
    }

    public function testArtisanSadPathReturns404WhenKeyIsInvalid(): void
    {
        $response = $this->postJson('/api/infra/artisan', [
            'command' => 'inspire',
        ], [
            'X-Infra-Key' => 'wrong-token',
        ]);

        $response->assertStatus(404);
    }

    public function testArtisanSadPathReturns404WhenArtisanDisabled(): void
    {
        config(['app_rules.infra.artisan_enabled' => false]);

        $response = $this->postJson('/api/infra/artisan', [
            'command' => 'inspire',
        ], [
            'X-Infra-Key' => 'valid-artisan-key',
        ]);

        $response->assertStatus(404);
    }

    public function testArtisanSadPathReturns422WhenCommandMissing(): void
    {
        $response = $this->postJson('/api/infra/artisan', [], [
            'X-Infra-Key' => 'valid-artisan-key',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The command parameter is required and must be a non-empty string.',
            ]);
    }
}
