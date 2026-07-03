<?php

use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return [
    'enabled' => env('HIDE_FORBIDDEN_ENABLED', app()->isProduction()),

    'mode' => env('HIDE_FORBIDDEN_MODE', 'middleware'),

    'status_codes' => [403],

    'exceptions' => [
        AuthorizationException::class,
        AccessDeniedHttpException::class,
    ],

    'only_routes' => [
        // 'admin.*',
    ],

    'except_routes' => [
        // 'login',
    ],

    'only_paths' => [
        // 'admin/*',
    ],

    'except_paths' => [
        // 'api/public/*',
    ],

    'guards' => [
        // 'web',
    ],

    'api_response' => [
        'message' => 'Not Found',
    ],

    'log_original_403' => false,
];
