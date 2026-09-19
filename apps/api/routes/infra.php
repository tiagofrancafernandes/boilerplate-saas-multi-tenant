<?php

declare(strict_types=1);

use App\Http\Controllers\Infra\SchedulerController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], '/scheduler', SchedulerController::class)
    ->name('infra.scheduler');
