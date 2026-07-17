<?php

/**
 * Sentry Laravel SDK configuration file.
 * Leave SENTRY_LARAVEL_DSN blank to keep Sentry fully disabled (its own
 * default behavior when no DSN is set) — nothing else here does anything
 * without it.
 *
 * @see https://docs.sentry.io/platforms/php/guides/laravel/configuration/options/
 */
return [

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    // The release version of your application — set this in your deploy
    // script (e.g. the git commit hash) so Sentry can group errors by release.
    'release' => env('SENTRY_RELEASE'),

    // Falls back to APP_ENV when left blank.
    'environment' => env('SENTRY_ENVIRONMENT'),

    'sample_rate' => env('SENTRY_SAMPLE_RATE') === null ? 1.0 : (float) env('SENTRY_SAMPLE_RATE'),
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE') === null ? null : (float) env('SENTRY_TRACES_SAMPLE_RATE'),
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE') === null ? null : (float) env('SENTRY_PROFILES_SAMPLE_RATE'),

    // This is a financial app — don't send request bodies/headers/user IP
    // by default. Turn on deliberately per-field if you need it, not blanket.
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    'ignore_transactions' => [
        '/up',
    ],

    'breadcrumbs' => [
        'logs' => env('SENTRY_BREADCRUMBS_LOGS_ENABLED', true),
        'cache' => env('SENTRY_BREADCRUMBS_CACHE_ENABLED', true),
        'sql_queries' => env('SENTRY_BREADCRUMBS_SQL_QUERIES_ENABLED', true),
        // Never log bound query parameters as breadcrumbs — this app's
        // queries touch money amounts, emails, and password reset tokens.
        'sql_bindings' => false,
        'queue_info' => env('SENTRY_BREADCRUMBS_QUEUE_INFO_ENABLED', true),
        'command_info' => env('SENTRY_BREADCRUMBS_COMMAND_JOBS_ENABLED', true),
        'http_client_requests' => env('SENTRY_BREADCRUMBS_HTTP_CLIENT_REQUESTS_ENABLED', true),
        'notifications' => env('SENTRY_BREADCRUMBS_NOTIFICATIONS_ENABLED', true),
    ],

    'tracing' => [
        'queue_job_transactions' => env('SENTRY_TRACE_QUEUE_ENABLED', true),
        'sql_queries' => env('SENTRY_TRACE_SQL_QUERIES_ENABLED', true),
        // Same reasoning as breadcrumbs.sql_bindings above.
        'sql_bindings' => false,
        'default_integrations' => env('SENTRY_TRACE_DEFAULT_INTEGRATIONS_ENABLED', true),
    ],

];
