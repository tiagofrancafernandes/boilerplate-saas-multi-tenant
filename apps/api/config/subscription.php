<?php

declare(strict_types=1);

return [
    'trial' => [
        'enabled' => (bool) env('SUBSCRIPTION_TRIAL_ENABLED', true),
        'days' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 14),
    ],

    'grace_period' => [
        'days' => (int) env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 3),
    ],

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'stripe'),
];
