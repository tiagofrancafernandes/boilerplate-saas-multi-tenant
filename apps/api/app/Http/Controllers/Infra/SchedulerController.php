<?php

declare(strict_types=1);

namespace App\Http\Controllers\Infra;

use App\Http\Controllers\Controller;
use App\Services\Domain\InfraSchedulerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

if (!class_exists(SchedulerController::class)) {
    final class SchedulerController extends Controller
    {
        public function __invoke(Request $request): JsonResponse
        {
            if (!InfraSchedulerService::isEnabled()) {
                throw new NotFoundHttpException('Not Found');
            }

            if (!InfraSchedulerService::isAuthorized($request)) {
                throw new NotFoundHttpException('Not Found');
            }

            $executionResult = InfraSchedulerService::runScheduler();

            return response()->json([
                'success' => true,
                'message' => 'Scheduler executed successfully.',
                'data' => $executionResult,
            ], 200);
        }
    }
}
