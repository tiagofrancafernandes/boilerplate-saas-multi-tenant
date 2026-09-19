<?php

declare(strict_types=1);

use App\Http\Middleware\EnforceSubscriptionAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: static function (): void {
            Route::prefix('api/public')
                ->middleware('api')
                ->group(base_path('routes/public.php'));

            Route::prefix('api/tenant')
                ->middleware(['api', 'tenant.subscription'])
                ->group(base_path('routes/tenant.php'));

            Route::prefix('api/admin')
                ->middleware('api')
                ->group(base_path('routes/admin.php'));

            foreach (['api/infra', 'infra'] as $prefix) {
                Route::prefix($prefix)
                    ->middleware('api')
                    ->group(base_path('routes/infra.php'));
            }
        },
    )
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.subscription' => EnforceSubscriptionAccess::class,
        ]);
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        //
    })->create();
