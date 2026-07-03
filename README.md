# Laravel Hide Forbidden

Hide selected Laravel `403 Forbidden` responses behind `404 Not Found` responses.

By default, the package is enabled only in production and runs in `middleware` mode. This keeps global exception handling unchanged until you opt routes into the behavior.

## Installation

Laravel discovers the service provider automatically through Composer package discovery.

Publish the config:

```bash
php artisan vendor:publish --tag=hide-forbidden-config
```

## Usage

Route-level usage:

```php
Route::middleware('hide-forbidden')->group(function () {
    Route::get('/admin/secret', SecretController::class);
});
```

Global usage:

```php
// config/hide-forbidden.php
'mode' => 'all',
```

You may also scope global conversion by named routes or paths:

```php
'mode' => 'routes',
'only_routes' => ['admin.*'],
```

```php
'mode' => 'paths',
'only_paths' => ['admin/*'],
```

JSON requests receive the configured `api_response` payload with a `404` status.
