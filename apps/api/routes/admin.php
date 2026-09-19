<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(static function (): void {
    Route::get('/tenants', static fn (): JsonResponse => response()->json([
        'tenants' => [],
    ]));
});
