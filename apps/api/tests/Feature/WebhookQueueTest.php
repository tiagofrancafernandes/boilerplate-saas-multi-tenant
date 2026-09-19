<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QueueDriver;
use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Infra\QStashSignatureVerifier;
use App\Services\Infra\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'webhook_queue.driver' => 'local',
            'webhook_queue.internal_secret' => 'test-internal-bypass-secret',
            'webhook_queue.header_bypass_name' => 'x-internal-webhook-bypass',
            'webhook_queue.qstash_token' => 'test-qstash-token',
            'webhook_queue.qstash_current_key' => 'test-signing-key-1',
            'webhook_queue.qstash_next_key' => 'test-signing-key-2',
            'webhook_queue.qstash_publish_url' => 'https://qstash.upstash.io/v2/publish/',
            'webhook_queue.endpoint_url' => 'http://localhost/api/infra/queue/process-task',
        ]);
    }

    public function testWebhookDispatcherResolvesLocalDriverInTestingEnvironment(): void
    {
        $resolvedDriver = WebhookDispatcher::resolveDriver();

        $this->assertSame(QueueDriver::LOCAL, $resolvedDriver);
    }

    public function testWebhookDispatcherDispatchesLocallyViaKernelHappyPath(): void
    {
        $result = WebhookDispatcher::dispatch('App\\Jobs\\SampleTestJob', [
            'item_id' => 42,
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame('local', $result['driver']);
        $this->assertSame(200, $result['status_code']);
        $this->assertSame('Task processed successfully', $result['response']['message'] ?? null);
        $this->assertSame('App\\Jobs\\SampleTestJob', $result['response']['action'] ?? null);
    }

    public function testWebhookDispatcherSkipsExecutionWhenDriverIsNone(): void
    {
        config(['webhook_queue.driver' => 'none']);

        // Temporarily bind testing to return false to test the driver fallback
        $this->app->detectEnvironment(static fn () => 'production');

        $result = WebhookDispatcher::dispatch('App\\Jobs\\SampleTestJob', [
            'item_id' => 99,
        ]);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('none', $result['driver']);

        $this->app->detectEnvironment(static fn () => 'testing');
    }

    public function testWebhookDispatcherDispatchesToQStashWhenConfigured(): void
    {
        Http::fake([
            'https://qstash.upstash.io/*' => Http::response(['messageId' => 'msg_123456'], 200),
        ]);

        config(['webhook_queue.driver' => 'qstash']);
        $this->app->detectEnvironment(static fn () => 'production');

        $result = WebhookDispatcher::dispatch('App\\Jobs\\SampleProductionJob', [
            'order_id' => 1001,
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame('qstash', $result['driver']);
        $this->assertSame(200, $result['status_code']);
        $this->assertSame('msg_123456', $result['response']['messageId'] ?? null);

        Http::assertSent(static function ($request) {
            $data = $request->data();

            return str_contains($request->url(), 'https://qstash.upstash.io/v2/publish/')
                && ($data['action'] ?? null) === 'App\\Jobs\\SampleProductionJob'
                && ($data['payload']['order_id'] ?? null) === 1001;
        });

        $this->app->detectEnvironment(static fn () => 'testing');
    }

    public function testQueueWebhookControllerHappyPathWithValidBypassHeader(): void
    {
        $response = $this->postJson('/api/infra/queue/process-task', [
            'action' => 'App\\Jobs\\ReportGenerator',
            'payload' => ['date' => '2026-09-19'],
        ], [
            'x-internal-webhook-bypass' => 'test-internal-bypass-secret',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Task processed successfully',
                'action' => 'App\\Jobs\\ReportGenerator',
            ]);
    }

    public function testQueueWebhookControllerSadPathMissingBypassHeaderReturns401(): void
    {
        $response = $this->postJson('/api/infra/queue/process-task', [
            'action' => 'App\\Jobs\\ReportGenerator',
            'payload' => [],
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized signature or bypass header',
            ]);
    }

    public function testQueueWebhookControllerSadPathInvalidBypassHeaderReturns401(): void
    {
        $response = $this->postJson('/api/infra/queue/process-task', [
            'action' => 'App\\Jobs\\ReportGenerator',
            'payload' => [],
        ], [
            'x-internal-webhook-bypass' => 'invalid-secret',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized signature or bypass header',
            ]);
    }

    public function testQueueWebhookControllerSadPathMissingActionReturns422(): void
    {
        $response = $this->postJson('/api/infra/queue/process-task', [
            'payload' => [],
        ], [
            'x-internal-webhook-bypass' => 'test-internal-bypass-secret',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Action parameter is required',
            ]);
    }

    public function testQueueWebhookControllerHappyPathWithQStashSignature(): void
    {
        config(['webhook_queue.driver' => 'qstash']);
        $this->app->detectEnvironment(static fn () => 'production');

        $rawBody = (string) json_encode([
            'action' => 'App\\Jobs\\UpstashJob',
            'payload' => ['user_id' => 12],
        ]);

        $validSignature = QStashSignatureVerifier::signPayload($rawBody, 'test-signing-key-1');

        $response = $this->call(
            'POST',
            '/api/infra/queue/process-task',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_UPSTASH_SIGNATURE' => $validSignature,
            ],
            $rawBody
        );

        $response->assertStatus(200);

        $this->app->detectEnvironment(static fn () => 'testing');
    }

    public function testQueueWebhookControllerSadPathWithInvalidQStashSignatureReturns401(): void
    {
        config(['webhook_queue.driver' => 'qstash']);
        $this->app->detectEnvironment(static fn () => 'production');

        $rawBody = (string) json_encode([
            'action' => 'App\\Jobs\\UpstashJob',
            'payload' => ['user_id' => 12],
        ]);

        $forgedSignature = 'invalid.jwt.signature';

        $response = $this->call(
            'POST',
            '/api/infra/queue/process-task',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_UPSTASH_SIGNATURE' => $forgedSignature,
            ],
            $rawBody
        );

        $response->assertStatus(401);

        $this->app->detectEnvironment(static fn () => 'testing');
    }

    public function testQueueWebhookControllerHappyPathWithTenantInitialization(): void
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::create([
            'id' => 'tenant_test_webhook',
            'name' => 'Webhook Tenant',
            'status' => TenantStatus::ACTIVE,
            'plan' => SubscriptionPlan::STARTER,
            'paid_until' => now()->addMonth(),
        ]);

        $response = $this->postJson('/api/infra/queue/process-task', [
            'action' => 'App\\Jobs\\TenantExportJob',
            'tenant_id' => $tenant->id,
            'payload' => ['entity' => 'invoices'],
        ], [
            'x-internal-webhook-bypass' => 'test-internal-bypass-secret',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'action' => 'App\\Jobs\\TenantExportJob',
                'tenant_id' => 'tenant_test_webhook',
            ]);

        $this->assertFalse(tenancy()->initialized);
    }

    public function testQueueWebhookControllerSadPathNonExistentTenantReturns404(): void
    {
        $response = $this->postJson('/api/infra/queue/process-task', [
            'action' => 'App\\Jobs\\TenantExportJob',
            'tenant_id' => 'non_existent_tenant_999',
            'payload' => [],
        ], [
            'x-internal-webhook-bypass' => 'test-internal-bypass-secret',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Tenant [non_existent_tenant_999] not found',
            ]);

        $this->assertFalse(tenancy()->initialized);
    }
}
