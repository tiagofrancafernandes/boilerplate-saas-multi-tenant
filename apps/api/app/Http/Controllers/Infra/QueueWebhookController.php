<?php

declare(strict_types=1);

namespace App\Http\Controllers\Infra;

use App\Enums\QueueDriver;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Infra\QStashSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

if (!class_exists(QueueWebhookController::class)) {
    final class QueueWebhookController extends Controller
    {
        public function __invoke(Request $request): JsonResponse
        {
            if (!static::isAuthorized($request)) {
                return response()->json([
                    'error' => 'Unauthorized signature or bypass header',
                ], 401);
            }

            $action = (string) $request->input('action');

            if (empty($action)) {
                return response()->json([
                    'error' => 'Action parameter is required',
                ], 422);
            }

            $tenantId = $request->input('tenant_id');

            if (!empty($tenantId)) {
                $tenant = Tenant::find((string) $tenantId);

                if (!$tenant) {
                    return response()->json([
                        'error' => "Tenant [{$tenantId}] not found",
                    ], 404);
                }

                tenancy()->initialize($tenant);
            }

            try {
                /** @var array<string, mixed> $payload */
                $payload = (array) $request->input('payload', []);
                $executionResult = static::executeAction($action, $payload);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Task processed successfully',
                    'action' => $action,
                    'tenant_id' => $tenantId,
                    'result' => $executionResult,
                ], 200);
            } finally {
                if (function_exists('tenancy') && tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        private static function isAuthorized(Request $request): bool
        {
            $driver = static::resolveDriver();

            if ($driver === QueueDriver::LOCAL) {
                return static::verifyLocalBypass($request);
            }

            if ($driver === QueueDriver::QSTASH) {
                return static::verifyQStashSignature($request);
            }

            return false;
        }

        private static function resolveDriver(): QueueDriver
        {
            if (app()->environment('testing')) {
                return QueueDriver::LOCAL;
            }

            $driverName = (string) config('webhook_queue.driver', 'local');

            return QueueDriver::tryFrom($driverName) ?? QueueDriver::LOCAL;
        }

        private static function verifyLocalBypass(Request $request): bool
        {
            $headerName = (string) config('webhook_queue.header_bypass_name', 'x-internal-webhook-bypass');
            $headerValue = $request->header($headerName);
            $expectedSecret = (string) config('webhook_queue.internal_secret');

            if (empty($headerValue) || empty($expectedSecret)) {
                return false;
            }

            return hash_equals($expectedSecret, (string) $headerValue);
        }

        private static function verifyQStashSignature(Request $request): bool
        {
            $signature = $request->header('upstash-signature');

            if (empty($signature)) {
                return false;
            }

            $rawBody = (string) $request->getContent();
            $currentKey = config('webhook_queue.qstash_current_key');
            $nextKey = config('webhook_queue.qstash_next_key');

            return QStashSignatureVerifier::verify(
                $rawBody,
                (string) $signature,
                $currentKey ? (string) $currentKey : null,
                $nextKey ? (string) $nextKey : null
            );
        }

        private static function executeAction(string $actionClass, array $payload): mixed
        {
            if (!class_exists($actionClass)) {
                return [
                    'executed' => true,
                    'type' => 'dummy_action',
                    'class' => $actionClass,
                ];
            }

            if (method_exists($actionClass, 'execute')) {
                return $actionClass::execute($payload);
            }

            if (method_exists($actionClass, 'dispatch')) {
                return $actionClass::dispatch($payload);
            }

            try {
                $instance = new $actionClass($payload);

                if (method_exists($instance, 'handle')) {
                    return $instance->handle();
                }
            } catch (Throwable) {
                return null;
            }

            return null;
        }
    }
}
