<?php

declare(strict_types=1);

namespace App\Services\Domain;

use Illuminate\Support\Facades\Artisan;

if (!class_exists(InfraArtisanService::class)) {
    final class InfraArtisanService
    {
        /**
         * Run an Artisan command and dispatch optional lifecycle webhooks.
         *
         * @param array<string, mixed> $parameters
         * @param mixed $webhooks
         * @return array{
         *     success: bool,
         *     exit_code: int,
         *     output: string,
         *     command: string,
         *     parameters: array<string, mixed>,
         *     executed_at: string
         * }
         */
        public static function runCommand(string $command, array $parameters = [], mixed $webhooks = null): array
        {
            $executedAt = now()->toIso8601String();

            try {
                $exitCode = Artisan::call($command, $parameters);
                $output = trim(Artisan::output());
                $success = $exitCode === 0;

                $result = [
                    'success' => $success,
                    'exit_code' => $exitCode,
                    'output' => $output,
                    'command' => $command,
                    'parameters' => $parameters,
                    'executed_at' => $executedAt,
                ];

                if ($success) {
                    InfraWebhookService::dispatch($webhooks, 'success', $result);
                }

                if (!$success) {
                    InfraWebhookService::dispatch($webhooks, 'error', $result);
                }

                return $result;
            } catch (\Throwable $e) {
                $errorResult = [
                    'success' => false,
                    'exit_code' => 1,
                    'output' => $e->getMessage(),
                    'command' => $command,
                    'parameters' => $parameters,
                    'executed_at' => $executedAt,
                ];

                InfraWebhookService::dispatch($webhooks, 'error', $errorResult);

                return $errorResult;
            } finally {
                InfraWebhookService::dispatch($webhooks, 'final', $result ?? $errorResult ?? []);
            }
        }
    }
}
