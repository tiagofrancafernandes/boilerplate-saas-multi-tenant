<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

if (!class_exists(InfraAuthService::class)) {
    final class InfraAuthService
    {
        public static function isSchedulerEnabled(): bool
        {
            return (bool) config('app_rules.infra.scheduler_enabled', true);
        }

        public static function isQueueEnabled(): bool
        {
            return (bool) config('app_rules.infra.queue_enabled', true);
        }

        public static function isArtisanEnabled(): bool
        {
            return (bool) config('app_rules.infra.artisan_enabled', true);
        }

        public static function isAuthorized(Request $request): bool
        {
            if (static::isStaticKeyValid($request)) {
                return true;
            }

            if (static::isSuperAdminUserValid($request)) {
                return true;
            }

            return false;
        }

        private static function isStaticKeyValid(Request $request): bool
        {
            /** @var list<string> $candidateTokens */
            $candidateTokens = array_values(array_filter([
                config('app_rules.infra.scheduler_auth_token'),
                config('app_rules.infra.auth_token'),
            ], static fn (mixed $token): bool => is_string($token) && $token !== ''));

            if (empty($candidateTokens)) {
                return false;
            }

            $candidateHeaders = array_values(array_unique(array_filter([
                config('app_rules.infra.scheduler_header_name'),
                config('app_rules.infra.header_name'),
                'X-Infra-Key',
            ], static fn (mixed $header): bool => is_string($header) && $header !== '')));

            foreach ($candidateHeaders as $headerName) {
                $providedHeader = $request->header($headerName);

                if (is_string($providedHeader) && $providedHeader !== '') {
                    foreach ($candidateTokens as $validToken) {
                        if (hash_equals($validToken, $providedHeader)) {
                            return true;
                        }
                    }
                }
            }

            $bearerToken = $request->bearerToken();

            if (is_string($bearerToken) && $bearerToken !== '') {
                foreach ($candidateTokens as $validToken) {
                    if (hash_equals($validToken, $bearerToken)) {
                        return true;
                    }
                }
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

            $role = (string) (config('app_rules.infra.role')
                ?? config('app_rules.infra.scheduler_role')
                ?? 'super-admin');

            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }

            $permission = (string) (config('app_rules.infra.permission')
                ?? config('app_rules.infra.scheduler_permission')
                ?? 'run-scheduler');

            if (method_exists($user, 'can') && $user->can($permission)) {
                return true;
            }

            return false;
        }
    }
}
