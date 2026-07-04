## Laravel Hide Forbidden

![Tests](https://img.shields.io/badge/tests-passing-brightgreen)
![Stable Version](https://img.shields.io/badge/stable-v1.1.0-blue)
![License](https://img.shields.io/badge/license-MIT-green)

**Don’t reveal what should stay hidden. 404 is better than 403 when the existence of a resource should remain private.**

Laravel Hide Forbidden improves your application’s security posture by converting selected `403 Forbidden` responses into `404 Not Found`. This helps prevent exposure of sensitive endpoints such as admin panels, tenant resources, private models, or internal APIs.

The idea for this package comes from a security insight shared by Nuno Maduro: *“404 is better than 403”* — emphasizing that security should not leak the existence of protected resources. Thanks to Nuno.

By default, the package is enabled only in production and works via middleware, giving you full control over which routes adopt this behavior without affecting Laravel’s global exception handling.

## Requirements

- PHP 8.1+
- Laravel 10 | 11 | 12 | 13

## Version

The current stable package version is `1.1.0`.

## Installation

Install the package:

```bash
composer require alexkramse/laravel-hide-forbidden
```

Laravel discovers the service provider automatically through Composer package discovery.

Publish the config:

```bash
php artisan vendor:publish --tag=hide-forbidden-config
```

## Usage

### Route-Level Usage

The safest way to use the package is to attach the `hide-forbidden` middleware only to routes where you want forbidden responses hidden:

```php
Route::middleware('hide-forbidden')->group(function (): void {
    Route::get('/admin/secret', SecretController::class);
});
```

With the default config, this route-level middleware mode is the only place conversion happens:

```php
'enabled' => env('HIDE_FORBIDDEN_ENABLED', env('APP_ENV', 'production') === 'production'),
'mode' => env('HIDE_FORBIDDEN_MODE', 'middleware'),
```

### Global Usage

To hide every matching `403` response in production, switch to `all` mode:

```php
// config/hide-forbidden.php
'mode' => 'all',
```

Use `except_routes` and `except_paths` to keep auth, public API, or other routes unchanged.

### Named Route Matching

Use `routes` mode when you only want to hide forbidden responses for specific named route groups:

```php
'mode' => 'routes',

'only_routes' => [
    'admin.*',
    'teams.members.*',
],
```

Route patterns use Laravel's `routeIs()` matching.

### Path Matching

Use `paths` mode when routes are not named or when URL patterns are clearer:

```php
'mode' => 'paths',

'only_paths' => [
    'admin/*',
    'internal/*',
],
```

Path patterns use Laravel request path matching.

### Excluding Auth Routes

Authentication routes are excluded by default so login, logout, registration, password reset, email verification, password confirmation, and Sanctum's SPA CSRF cookie endpoint keep their normal behavior:

```php
'except_routes' => [
    'login',
    'logout',
    'register',
    'password.*',
    'verification.*',
    'sanctum.csrf-cookie',
],
```

Add your own routes when they should keep returning a real `403`

### JSON Responses

JSON requests receive the configured payload with a `404` status:

```php
'api_response' => [
    'message' => 'Not Found',
],
```

Example response:

```json
{
    "message": "Not Found"
}
```

### Guard Matching

By default, all guards are eligible. To convert only when a specific guard is authenticated:

```php
'guards' => [
    'admin',
],
```

When `guards` is empty, no guard check is required.

### Logging

To log every hidden forbidden response:

```php
'log_original_403' => true,
```

The log entry includes the route name and path, while the client still receives a `404`.

## What Gets Converted

By default, the package converts:

- `Illuminate\Auth\Access\AuthorizationException`
- `Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException`
- Any Symfony HTTP exception with status code `403`
- Route middleware responses that already rendered as `403`

Non-forbidden server errors are not converted.

## Testing

```bash
composer test
```
