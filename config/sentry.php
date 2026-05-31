<?php

return [
    'dsn' => env('SENTRY_ENABLED', false) ? env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')) : null,

    'environment' => env('APP_ENV'),

    'release' => env('APP_VERSION'),

    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue_info' => true,
        'command_info' => true,
        'notifications' => true,
    ],

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    'tracing' => [
        'enabled' => env('SENTRY_TRACING_ENABLED', false),
        'routes' => [
            'enabled' => env('SENTRY_TRACING_ROUTES_ENABLED', true),
        ],
    ],
];
