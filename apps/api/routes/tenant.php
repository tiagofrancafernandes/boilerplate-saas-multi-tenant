<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/profile', static fn (): JsonResponse => response()->json([
    'message' => 'Tenant profile endpoint',
]));

Route::post('/resources', static fn (Request $request): JsonResponse => response()->json([
    'message' => 'Resource created successfully',
    'resource_id' => 'res_demo_' . uniqid(),
], 201));
