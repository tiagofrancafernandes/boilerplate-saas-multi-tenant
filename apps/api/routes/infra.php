<?php

declare(strict_types=1);

use App\Http\Controllers\Infra\ArtisanController;
use App\Http\Controllers\Infra\QueueController;
use App\Http\Controllers\Infra\QueueWebhookController;
use App\Http\Controllers\Infra\SchedulerController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], '/scheduler', SchedulerController::class)
    ->name('infra.scheduler');

Route::post('/queue', QueueController::class)
    ->name('infra.queue');

Route::post('/queue/process-task', QueueWebhookController::class)
    ->name('infra.queue.process_task');

Route::post('/artisan', ArtisanController::class)
    ->name('infra.artisan');
