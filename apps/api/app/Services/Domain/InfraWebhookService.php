<?php

declare(strict_types=1);

namespace App\Services\Domain;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

if (!class_exists(InfraWebhookService::class)) {
    final class InfraWebhookService
    {
        /**
         * Supported HTTP methods for webhook dispatch.
         *
         * @var list<string>
         */
        private const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        /**
         * Dispatch a webhook configuration safely.
         *
         * @param mixed $webhooks
         * @param 'success'|'error'|'final' $type
         * @param array<string, mixed> $context
         */
        public static function dispatch(mixed $webhooks, string $type, array $context = []): bool
        {
            if (!is_array($webhooks)) {
                return false;
            }

            $config = $webhooks[$type] ?? null;

            if (!is_array($config)) {
                return false;
            }

            $url = $config['url'] ?? null;

            if (!is_string($url) || trim($url) === '') {
                Log::debug(sprintf('Infra webhook (%s) skipped: url is not a non-empty string.', $type));

                return false;
            }

            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                Log::warning(sprintf('Infra webhook (%s) skipped: invalid URL "%s".', $type, $url));

                return false;
            }

            $method = strtoupper((string) ($config['method'] ?? 'POST'));

            if (!in_array($method, static::ALLOWED_METHODS, true)) {
                $method = 'POST';
            }

            /** @var array<string, string> $headers */
            $headers = is_array($config['headers'] ?? null) ? $config['headers'] : [];
            $body = static::resolveBody($config['body'] ?? null, $type, $context);

            return static::sendHttpRequest($url, $method, $headers, $body, $type);
        }

        /**
         * @param mixed $customBody
         * @param array<string, mixed> $context
         * @return array<string, mixed>
         */
        private static function resolveBody(mixed $customBody, string $type, array $context): array
        {
            if (is_array($customBody)) {
                return $customBody;
            }

            return [
                'status' => $type,
                'content' => is_string($customBody) ? $customBody : sprintf('Execution completed with status: %s', $type),
                'context' => $context,
                'dispatched_at' => now()->toIso8601String(),
            ];
        }

        /**
         * @param array<string, string> $headers
         * @param array<string, mixed> $body
         */
        private static function sendHttpRequest(
            string $url,
            string $method,
            array $headers,
            array $body,
            string $type
        ): bool {
            try {
                $timeout = (int) config('app_rules.infra.webhook_timeout_seconds', 5);
                $request = Http::timeout($timeout)->withHeaders($headers);

                $response = match ($method) {
                    'GET' => $request->get($url, $body),
                    'PUT' => $request->put($url, $body),
                    'PATCH' => $request->patch($url, $body),
                    'DELETE' => $request->delete($url, $body),
                    default => $request->post($url, $body),
                };

                if (!$response->successful()) {
                    Log::warning(sprintf('Infra webhook (%s) non-2xx status %d from %s', $type, $response->status(), $url), [
                        'type' => $type,
                        'url' => $url,
                        'status' => $response->status(),
                    ]);

                    return false;
                }

                return true;
            } catch (\Throwable $e) {
                Log::warning(sprintf('Infra webhook (%s) error contacting %s: %s', $type, $url, $e->getMessage()), [
                    'type' => $type,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }
    }
}
