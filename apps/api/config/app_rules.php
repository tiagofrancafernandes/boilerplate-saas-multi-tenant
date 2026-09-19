<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Infrastructure & Operational Rules
    |--------------------------------------------------------------------------
    |
    | Configuration options for infrastructure endpoints, background triggers,
    | schedulers, and operational health/monitoring tasks.
    |
    */
    'infra' => [
        'scheduler_enabled' => (bool) env('INFRA_SCHEDULER_ENABLED', true),
        'queue_enabled' => (bool) env('INFRA_QUEUE_ENABLED', true),
        'artisan_enabled' => (bool) env('INFRA_ARTISAN_ENABLED', true),
        'auth_token' => env('INFRA_AUTH_TOKEN', env('INFRA_SCHEDULER_AUTH_TOKEN', null)),
        'scheduler_auth_token' => env('INFRA_SCHEDULER_AUTH_TOKEN', null),
        'header_name' => env('INFRA_HEADER_NAME', env('INFRA_SCHEDULER_HEADER_NAME', 'X-Infra-Key')),
        'scheduler_header_name' => env('INFRA_SCHEDULER_HEADER_NAME', 'X-Infra-Key'),
        'role' => env('INFRA_ROLE', env('INFRA_SCHEDULER_ROLE', 'super-admin')),
        'scheduler_role' => env('INFRA_SCHEDULER_ROLE', 'super-admin'),
        'permission' => env('INFRA_PERMISSION', env('INFRA_SCHEDULER_PERMISSION', 'run-scheduler')),
        'scheduler_permission' => env('INFRA_SCHEDULER_PERMISSION', 'run-scheduler'),
        'webhook_timeout_seconds' => (int) env('INFRA_WEBHOOK_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeding & Demo Data Rules
    |--------------------------------------------------------------------------
    |
    | Controls whether demo users and demonstration tenants can be seeded.
    | In non-production environments, this defaults to true. In production,
    | it is strictly disabled unless SEED_DEMO_USERS is set to true.
    |
    */
    'seed_demo_users' => (bool) env('SEED_DEMO_USERS', false),
];
