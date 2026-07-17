<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    | The SPA is served from the same origin as the API, so CORS is locked
    | to the app's own URL. Add extra origins here only if you ship a
    | separate frontend (e.g. a mobile web build on another domain).
    */

    'paths' => ['api/*', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('APP_URL', 'http://localhost')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Required for Sanctum's SPA cookie authentication — the browser will
    // only send/receive the session + XSRF-TOKEN cookies on XHR/fetch if
    // the server explicitly allows credentialed requests. Safe specifically
    // because allowed_origins above is a concrete APP_URL, never '*'
    // (browsers refuse to combine a wildcard origin with credentials:true).
    'supports_credentials' => true,

];
