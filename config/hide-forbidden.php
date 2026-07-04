<?php

use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Forbidden Hiding
    |--------------------------------------------------------------------------
    |
    | The package is enabled in production by default. Keep this disabled in
    | local and testing environments unless you are explicitly testing how
    | hidden authorization failures behave.
    |
    */
    'enabled' => env('HIDE_FORBIDDEN_ENABLED', env('APP_ENV', 'production') === 'production'),

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    |
    | Controls where 403 responses are converted to 404 responses.
    |
    | Supported modes:
    |
    | - "middleware": only routes using the "hide-forbidden" middleware are
    |   converted. This is the safest default because it never changes global
    |   exception rendering unless a route opts in.
    |
    | - "all": every matching 403 response is converted, except routes and
    |   paths listed in "except_routes" or "except_paths".
    |
    | - "routes": matching 403 responses are converted only when the current
    |   named route matches one of the patterns in "only_routes".
    |
    | - "paths": matching 403 responses are converted only when the request
    |   path matches one of the patterns in "only_paths".
    |
    */
    'mode' => env('HIDE_FORBIDDEN_MODE', 'middleware'),

    /*
    |--------------------------------------------------------------------------
    | Matched Forbidden Responses
    |--------------------------------------------------------------------------
    |
    | The package only hides exceptions or responses with these status codes.
    | Leave this as [403] unless you intentionally want to hide another status.
    |
    */
    'status_codes' => [403],

    /*
    |--------------------------------------------------------------------------
    | Matched Exceptions
    |--------------------------------------------------------------------------
    |
    | These exception classes are treated as forbidden responses. Any Symfony
    | HTTP exception with a status listed in "status_codes" is also matched.
    |
    */
    'exceptions' => [
        AuthorizationException::class,
        AccessDeniedHttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Include Rules
    |--------------------------------------------------------------------------
    |
    | Used by "routes" and "paths" modes. Route patterns use routeIs() matching
    | against named routes. Path patterns use request path matching.
    |
    */
    'only_routes' => [
        // 'admin.*',
    ],

    'only_paths' => [
        // 'admin/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude Rules
    |--------------------------------------------------------------------------
    |
    | These route and path patterns are never converted, regardless of mode.
    | Authentication routes are excluded by default so login, registration,
    | password reset, email verification, password confirmation, and Sanctum's
    | SPA CSRF cookie endpoint keep their normal behavior.
    |
    */
    'except_routes' => [
        'login',
        'logout',
        'register',
        'password.*',
        'verification.*',
        'sanctum.csrf-cookie',
    ],

    'except_paths' => [
        // 'api/public/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    |
    | When empty, all guards are eligible. When one or more guards are listed,
    | conversion only happens if the current request is authenticated by one of
    | those guards.
    |
    */
    'guards' => [
        // 'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Response
    |--------------------------------------------------------------------------
    |
    | JSON requests receive this body with a 404 status code.
    |
    */
    'api_response' => [
        'message' => 'Not Found',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable this to write a notice when a 403 is hidden as a 404. The log entry
    | includes the route name and path, but avoids leaking details to clients.
    |
    */
    'log_original_403' => false,
];
