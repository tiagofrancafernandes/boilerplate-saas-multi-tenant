<?php

declare(strict_types=1);

namespace App\Services\Infra;

use App\Enums\QueueDriver;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

if (!class_exists(WebhookDispatcher::class)) {
    final class WebhookDispatcher
    {
        public static function resolveDriver(): QueueDriver
        {
            if (app()->environment('testing')) {
                return QueueDriver::LOCAL;
            }

            $driverName = (string) config('webhook_queue.driver', 'local');

            return QueueDriver::tryFrom($driverName) ?? QueueDriver::LOCAL;
        }

        public static function dispatch(string $actionClass, array $payload = [], ?string $tenantId = null): array
        {
            $driver = static::resolveDriver();

            if ($driver === QueueDriver::NONE) {
                return [
                    'status' => 'skipped',
                    'driver' => QueueDriver::NONE->value,
                    'action' => $actionClass,
                ];
            }

            $data = [
                'action' => $actionClass,
                'tenant_id' => $tenantId,
                'payload' => $payload,
                'dispatched_at' => now()->toIso8601String(),
            ];

            if ($driver === QueueDriver::LOCAL) {
                return static::dispatchLocally($data);
            }

            if ($driver === QueueDriver::QSTASH) {
                return static::dispatchToQStash($data);
            }

            return [
                'status' => 'unsupported_driver',
                'driver' => $driver->value,
                'action' => $actionClass,
            ];
        }

        private static function dispatchLocally(array $data): array
        {
            $secret = (string) config('webhook_queue.internal_secret');
            $headerName = (string) config('webhook_queue.header_bypass_name', 'x-internal-webhook-bypass');
            $serverHeaderKey = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));

            $serverVars = [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                $serverHeaderKey => $secret,
            ];

            $request = Request::create(
                '/api/infra/queue/process-task',
                'POST',
                [],
                [],
                [],
                $serverVars,
                (string) json_encode($data)
            );

            $response = app(Kernel::class)->handle($request);
            $statusCode = $response->getStatusCode();
            $content = json_decode($response->getContent() ?: '{}', true) ?: [];

            return [
                'status' => ($statusCode >= 200 && $statusCode < 300) ? 'success' : 'failed',
                'driver' => QueueDriver::LOCAL->value,
                'status_code' => $statusCode,
                'response' => $content,
            ];
        }

        private static function dispatchToQStash(array $data): array
        {
            $destinationUrl = config('webhook_queue.endpoint_url') ?: url('/api/infra/queue/process-task');
            $publishBaseUrl = rtrim((string) config('webhook_queue.qstash_publish_url', 'https://qstash.upstash.io/v2/publish/'), '/');
            $targetUrl = "{$publishBaseUrl}/{$destinationUrl}";

            $token = (string) config('webhook_queue.qstash_token');

            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->post($targetUrl, $data);

            return [
                'status' => $response->successful() ? 'success' : 'failed',
                'driver' => QueueDriver::QSTASH->value,
                'status_code' => $response->status(),
                'response' => $response->json() ?? [],
            ];
        }
    }
}
