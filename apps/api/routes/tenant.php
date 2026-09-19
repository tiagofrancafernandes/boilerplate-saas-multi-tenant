<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/profile', static fn (): JsonResponse => response()->json([
    'message' => 'Tenant profile endpoint',
]));
