<?php

declare(strict_types=1);

use App\Http\Controllers\UserPreferencesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(static function (): void {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::get('/user/preferences', [UserPreferencesController::class, 'show'])->name('user.preferences.show');
    Route::put('/user/preferences', [UserPreferencesController::class, 'update'])->name('user.preferences.update');
});
