<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Domain\SubscriptionAccessService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSubscriptionAccess
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentTenant = tenant();

        if (!$currentTenant instanceof Tenant) {
            return $next($request);
        }

        $shouldWarn = SubscriptionAccessService::shouldWarnSubscription($currentTenant);
        $isWriteRequest = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        if ($isWriteRequest) {
            $canWrite = SubscriptionAccessService::canPerformWrite($currentTenant);

            if (!$canWrite) {
                return new JsonResponse([
                    'message' => 'Subscription expired or unpaid. Workspace is in read-only mode.',
                    'error' => 'SUBSCRIPTION_READ_ONLY',
                    'paid_until' => $currentTenant->paid_until?->toIso8601String(),
                    'trial_ends_at' => $currentTenant->trial_ends_at?->toIso8601String(),
                ], 403);
            }
        }

        $response = $next($request);

        if ($shouldWarn) {
            $response->headers->set('X-Subscription-Warning', 'Payment failed. Grace period is currently active.');
        }

        return $response;
    }
}
