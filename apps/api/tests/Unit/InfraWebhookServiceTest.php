<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Domain\InfraWebhookService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InfraWebhookServiceTest extends TestCase
{
    public function testDispatchSuccessWebhookHappyPath(): void
    {
        Http::fake([
            'https://webhook.site/test-success' => Http::response(['status' => 'received'], 200),
        ]);

        $webhooks = [
            'success' => [
                'url' => 'https://webhook.site/test-success',
                'method' => 'POST',
                'headers' => ['X-Custom' => 'header-val'],
                'body' => [
                    'status' => 'success',
                    'content' => 'Queue completed',
                ],
            ],
        ];

        $dispatched = InfraWebhookService::dispatch($webhooks, 'success', ['job_id' => 123]);

        $this->assertTrue($dispatched);

        Http::assertSent(fn ($request) => $request->url() === 'https://webhook.site/test-success'
                && $request->method() === 'POST'
                && $request->hasHeader('X-Custom', 'header-val')
                && $request['status'] === 'success'
                && $request['content'] === 'Queue completed');
    }

    public function testDispatchFinalWebhookWithDefaultBodyHappyPath(): void
    {
        Http::fake([
            'https://webhook.site/test-final' => Http::response('OK', 200),
        ]);

        $webhooks = [
            'final' => [
                'url' => 'https://webhook.site/test-final',
                'method' => 'PUT',
            ],
        ];

        $dispatched = InfraWebhookService::dispatch($webhooks, 'final', ['exit_code' => 0]);

        $this->assertTrue($dispatched);

        Http::assertSent(fn ($request) => $request->url() === 'https://webhook.site/test-final'
                && $request->method() === 'PUT'
                && $request['status'] === 'final'
                && isset($request['context']));
    }

    public function testDispatchSkipsWhenWebhookConfigMissingSadPath(): void
    {
        Http::fake();

        $this->assertFalse(InfraWebhookService::dispatch(null, 'success'));
        $this->assertFalse(InfraWebhookService::dispatch([], 'success'));
        $this->assertFalse(InfraWebhookService::dispatch(['error' => []], 'success'));

        Http::assertNothingSent();
    }

    public function testDispatchSkipsWhenUrlInvalidSadPath(): void
    {
        Http::fake();

        $webhooks = [
            'error' => [
                'url' => 'not-a-valid-url',
            ],
        ];

        $dispatched = InfraWebhookService::dispatch($webhooks, 'error');

        $this->assertFalse($dispatched);
        Http::assertNothingSent();
    }

    public function testDispatchCatchesHttpExceptionWithoutFailingSadPath(): void
    {
        Http::fake([
            'https://webhook.site/timeout' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $webhooks = [
            'error' => [
                'url' => 'https://webhook.site/timeout',
            ],
        ];

        // Should return false and never rethrow exception
        $dispatched = InfraWebhookService::dispatch($webhooks, 'error');

        $this->assertFalse($dispatched);
    }

    public function testDispatchReturnsFalseOnHttpNon2xxResponseSadPath(): void
    {
        Http::fake([
            'https://webhook.site/error-500' => Http::response(['error' => 'server error'], 500),
        ]);

        $webhooks = [
            'success' => [
                'url' => 'https://webhook.site/error-500',
            ],
        ];

        $dispatched = InfraWebhookService::dispatch($webhooks, 'success');

        $this->assertFalse($dispatched);
    }
}
