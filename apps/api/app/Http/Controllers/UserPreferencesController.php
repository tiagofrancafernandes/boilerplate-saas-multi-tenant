<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UserPreferencesRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

if (!class_exists(UserPreferencesController::class)) {
    final class UserPreferencesController extends Controller
    {
        public function show(Request $request): JsonResponse
        {
            /** @var User $user */
            $user = $request->user();

            return response()->json([
                'data' => $user->getPreferencesWithDefaults(),
            ], 200);
        }

        public function update(UserPreferencesRequest $request): JsonResponse
        {
            /** @var User $user */
            $user = $request->user();

            $validated = $request->validated();
            $preferences = $user->updatePreferences($validated);

            return response()->json([
                'message' => 'Preferences updated successfully.',
                'data' => $preferences,
            ], 200);
        }
    }
}
