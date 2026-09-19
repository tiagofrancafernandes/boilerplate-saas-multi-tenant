<?php

declare(strict_types=1);

namespace App\Services\Domain;

use Illuminate\Support\Facades\Artisan;

if (!class_exists(InfraQueueService::class)) {
    final class InfraQueueService
    {
        /**
         * Run queue worker with given parameters and dispatch optional webhooks.
         *
         * @param array<string, mixed> $options
         * @param mixed $webhooks
         * @return array{
         *     success: bool,
         *     exit_code: int,
         *     output: string,
         *     parameters: array<string, mixed>,
         *     executed_at: string
         * }
         */
        public static function runWorker(array $options = [], mixed $webhooks = null): array
        {
            $params = static::buildWorkerParameters($options);
            $executedAt = now()->toIso8601String();

            try {
                $exitCode = Artisan::call('queue:work', $params);
                $output = trim(Artisan::output());
                $success = $exitCode === 0;

                $result = [
                    'success' => $success,
                    'exit_code' => $exitCode,
                    'output' => $output,
                    'parameters' => $params,
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
                    'parameters' => $params,
                    'executed_at' => $executedAt,
                ];

                InfraWebhookService::dispatch($webhooks, 'error', $errorResult);

                return $errorResult;
            } finally {
                InfraWebhookService::dispatch($webhooks, 'final', $result ?? $errorResult ?? []);
            }
        }

        /**
         * @param array<string, mixed> $options
         * @return array<string, mixed>
         */
        private static function buildWorkerParameters(array $options): array
        {
            $params = [];

            if (!empty($options['connection']) && is_string($options['connection'])) {
                $params['connection'] = $options['connection'];
            }

            if (!empty($options['queue']) && is_string($options['queue'])) {
                $params['--queue'] = $options['queue'];
            }

            $stopWhenEmpty = (bool) ($options['stop_when_empty'] ?? true);

            if ($stopWhenEmpty) {
                $params['--stop-when-empty'] = true;
            }

            $maxJobs = isset($options['max_jobs']) ? (int) $options['max_jobs'] : 1;

            if ($maxJobs > 0) {
                $params['--max-jobs'] = $maxJobs;
            }

            if (!empty($options['max_time'])) {
                $params['--max-time'] = (int) $options['max_time'];
            }

            if (!empty($options['tries'])) {
                $params['--tries'] = (int) $options['tries'];
            }

            if (!empty($options['timeout'])) {
                $params['--timeout'] = (int) $options['timeout'];
            }

            if (!empty($options['memory'])) {
                $params['--memory'] = (int) $options['memory'];
            }

            if (isset($options['sleep'])) {
                $params['--sleep'] = (int) $options['sleep'];
            }

            if (isset($options['rest'])) {
                $params['--rest'] = (int) $options['rest'];
            }

            return $params;
        }
    }
}
