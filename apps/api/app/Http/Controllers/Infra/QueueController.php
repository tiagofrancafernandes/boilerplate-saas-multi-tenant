<?php

declare(strict_types=1);

namespace App\Http\Controllers\Infra;

use App\Http\Controllers\Controller;
use App\Services\Domain\InfraAuthService;
use App\Services\Domain\InfraQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

if (!class_exists(QueueController::class)) {
    final class QueueController extends Controller
    {
        public function __invoke(Request $request): JsonResponse
        {
            if (!InfraAuthService::isQueueEnabled()) {
                throw new NotFoundHttpException('Not Found');
            }

            if (!InfraAuthService::isAuthorized($request)) {
                throw new NotFoundHttpException('Not Found');
            }

            /** @var array<string, mixed> $options */
            $options = $request->all();
            unset($options['webhooks']);

            $webhooks = $request->input('webhooks');

            $executionResult = InfraQueueService::runWorker($options, $webhooks);

            return response()->json([
                'success' => $executionResult['success'],
                'message' => $executionResult['success'] ? 'Queue worker executed successfully.' : 'Queue worker encountered an error.',
                'data' => $executionResult,
            ], 200);
        }
    }
}
