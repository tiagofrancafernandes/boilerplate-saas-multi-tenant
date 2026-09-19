<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Queue Driver
    |--------------------------------------------------------------------------
    |
    | Defines the driver used to dispatch asynchronous background jobs.
    | Options: 'local' (internal HTTP bypass), 'qstash' (Upstash QStash push),
    | 'redis' (traditional daemon worker), 'none' (disabled).
    | Automatically switches to 'qstash' when running in serverless (e.g., Vercel).
    |
    */
    'driver' => env(
        'WEBHOOK_QUEUE_DRIVER',
        (bool) (env('VERCEL') || env('IS_SERVERLESS')) ? 'qstash' : 'local'
    ),

    /*
    |--------------------------------------------------------------------------
    | Internal Bypass Configuration (Local & Testing)
    |--------------------------------------------------------------------------
    |
    | Secret key and HTTP header used to authorize local synchronous dispatches
    | via the Laravel HTTP Kernel without contacting external networks.
    |
    */
    'internal_secret' => env('WEBHOOK_INTERNAL_SECRET', 'boilerplate-internal-bypass-secret'),
    'header_bypass_name' => env('WEBHOOK_BYPASS_HEADER', 'x-internal-webhook-bypass'),

    /*
    |--------------------------------------------------------------------------
    | Upstash QStash Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials for publishing messages and verifying incoming webhooks
    | from Upstash QStash in serverless deployments.
    |
    */
    'qstash_token' => env('QSTASH_TOKEN'),
    'qstash_current_key' => env('QSTASH_CURRENT_SIGNING_KEY'),
    'qstash_next_key' => env('QSTASH_NEXT_SIGNING_KEY'),
    'qstash_publish_url' => env('QSTASH_PUBLISH_URL', 'https://qstash.upstash.io/v2/publish/'),
    'endpoint_url' => env('WEBHOOK_QUEUE_ENDPOINT_URL', null),
];
