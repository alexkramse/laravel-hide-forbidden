<?php

namespace Alexkramse\LaravelHideForbidden\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class RequestMatcher
{
    public function shouldHide(Throwable $exception, Request $request, bool $force = false): bool
    {
        if (! (bool) config('hide-forbidden.enabled', false)) {
            return false;
        }

        if (! $this->matchesException($exception)) {
            return false;
        }

        if (! $force && config('hide-forbidden.mode', 'middleware') === 'middleware') {
            return false;
        }

        if ($this->isExcepted($request)) {
            return false;
        }

        if (! $this->matchesMode($request, $force)) {
            return false;
        }

        return $this->matchesGuard();
    }

    public function shouldHideResponse(Response $response, Request $request, bool $force = false): bool
    {
        if (! (bool) config('hide-forbidden.enabled', false)) {
            return false;
        }

        if (! in_array($response->getStatusCode(), (array) config('hide-forbidden.status_codes', [403]), true)) {
            return false;
        }

        if (! $force && config('hide-forbidden.mode', 'middleware') === 'middleware') {
            return false;
        }

        if ($this->isExcepted($request)) {
            return false;
        }

        if (! $this->matchesMode($request, $force)) {
            return false;
        }

        return $this->matchesGuard();
    }

    private function matchesException(Throwable $exception): bool
    {
        foreach ((array) config('hide-forbidden.exceptions', []) as $exceptionClass) {
            if ($exception instanceof $exceptionClass) {
                return true;
            }
        }

        if ($exception instanceof AuthorizationException) {
            return true;
        }

        return $exception instanceof HttpExceptionInterface
            && in_array($exception->getStatusCode(), (array) config('hide-forbidden.status_codes', [403]), true);
    }

    private function isExcepted(Request $request): bool
    {
        return $this->matchesRoutePatterns($request, (array) config('hide-forbidden.except_routes', []))
            || $this->matchesPathPatterns($request, (array) config('hide-forbidden.except_paths', []));
    }

    private function matchesMode(Request $request, bool $force): bool
    {
        $mode = (string) config('hide-forbidden.mode', 'middleware');

        if ($force || $mode === 'all') {
            return true;
        }

        if ($mode === 'routes') {
            return $this->matchesRoutePatterns($request, (array) config('hide-forbidden.only_routes', []));
        }

        if ($mode === 'paths') {
            return $this->matchesPathPatterns($request, (array) config('hide-forbidden.only_paths', []));
        }

        return false;
    }

    private function matchesRoutePatterns(Request $request, array $patterns): bool
    {
        $routeName = $request->route()?->getName();

        if ($routeName === null) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPathPatterns(Request $request, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function matchesGuard(): bool
    {
        $guards = (array) config('hide-forbidden.guards', []);

        if ($guards === []) {
            return true;
        }

        foreach ($guards as $guard) {
            if (auth()->guard($guard)->check()) {
                return true;
            }
        }

        return false;
    }
}
