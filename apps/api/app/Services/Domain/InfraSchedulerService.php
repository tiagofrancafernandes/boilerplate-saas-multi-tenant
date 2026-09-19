<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

if (!class_exists(InfraSchedulerService::class)) {
    final class InfraSchedulerService
    {
        public static function isEnabled(): bool
        {
            return (bool) config('app_rules.infra.scheduler_enabled', true);
        }

        public static function isAuthorized(Request $request): bool
        {
            return InfraAuthService::isAuthorized($request);
        }

        public static function runScheduler(mixed $webhooks = null): array
        {
            $executedAt = now()->toIso8601String();

            try {
                $exitCode = Artisan::call('schedule:run');
                $output = trim(Artisan::output());
                $success = $exitCode === 0;

                $result = [
                    'success' => $success,
                    'exit_code' => $exitCode,
                    'output' => $output,
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
                    'executed_at' => $executedAt,
                ];

                InfraWebhookService::dispatch($webhooks, 'error', $errorResult);

                return $errorResult;
            } finally {
                InfraWebhookService::dispatch($webhooks, 'final', $result ?? $errorResult ?? []);
            }
        }

        private static function isStaticKeyValid(Request $request): bool
        {
            /** @var string|null $staticToken */
            $staticToken = config('app_rules.infra.scheduler_auth_token');

            if (empty($staticToken)) {
                return false;
            }

            $headerName = (string) config('app_rules.infra.scheduler_header_name', 'X-Infra-Key');
            $providedHeader = $request->header($headerName);

            if (is_string($providedHeader) && hash_equals($staticToken, $providedHeader)) {
                return true;
            }

            $bearerToken = $request->bearerToken();

            if (is_string($bearerToken) && hash_equals($staticToken, $bearerToken)) {
                return true;
            }

            return false;
        }

        private static function isSuperAdminUserValid(Request $request): bool
        {
            /** @var User|null $user */
            $user = Auth::guard('sanctum')->user() ?? $request->user('sanctum');

            if ($user === null) {
                return false;
            }

            $role = (string) config('app_rules.infra.scheduler_role', 'super-admin');

            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }

            $permission = (string) config('app_rules.infra.scheduler_permission', 'run-scheduler');

            if (method_exists($user, 'can') && $user->can($permission)) {
                return true;
            }

            return false;
        }
    }
}
