<?php

namespace Alexkramse\LaravelHideForbidden;

use Alexkramse\LaravelHideForbidden\Middleware\HideForbiddenMiddleware;
use Alexkramse\LaravelHideForbidden\Support\RequestMatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Throwable;

class HideForbiddenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/hide-forbidden.php', 'hide-forbidden');

        $this->app->singleton(RequestMatcher::class);
        $this->app->singleton(HideForbidden::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/hide-forbidden.php' => config_path('hide-forbidden.php'),
        ], 'hide-forbidden-config');

        $this->app->make(Router::class)->aliasMiddleware('hide-forbidden', HideForbiddenMiddleware::class);

        $this->app->afterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! method_exists($handler, 'renderable')) {
                return;
            }

            $handler->renderable(function (Throwable $exception, $request) {
                return $this->app->make(HideForbidden::class)->render($exception, $request);
            });
        });
    }
}
