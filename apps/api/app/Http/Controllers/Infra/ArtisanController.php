<?php

declare(strict_types=1);

namespace App\Http\Controllers\Infra;

use App\Http\Controllers\Controller;
use App\Services\Domain\InfraArtisanService;
use App\Services\Domain\InfraAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

if (!class_exists(ArtisanController::class)) {
    final class ArtisanController extends Controller
    {
        public function __invoke(Request $request): JsonResponse
        {
            if (!InfraAuthService::isArtisanEnabled()) {
                throw new NotFoundHttpException('Not Found');
            }

            if (!InfraAuthService::isAuthorized($request)) {
                throw new NotFoundHttpException('Not Found');
            }

            $command = $request->input('command');

            if (!is_string($command) || trim($command) === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'The command parameter is required and must be a non-empty string.',
                ], 422);
            }

            /** @var array<string, mixed> $parameters */
            $parameters = is_array($request->input('parameters')) ? $request->input('parameters') : [];
            $arguments = is_array($request->input('arguments')) ? $request->input('arguments') : [];

            $mergedParameters = array_merge($arguments, $parameters);
            $webhooks = $request->input('webhooks');

            $executionResult = InfraArtisanService::runCommand($command, $mergedParameters, $webhooks);

            return response()->json([
                'success' => $executionResult['success'],
                'message' => $executionResult['success'] ? 'Artisan command executed successfully.' : 'Artisan command encountered an error.',
                'data' => $executionResult,
            ], 200);
        }
    }
}
