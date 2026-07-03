<?php

namespace Alexkramse\LaravelHideForbidden\Middleware;

use Alexkramse\LaravelHideForbidden\HideForbidden;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HideForbiddenMiddleware
{
    public function __construct(
        private readonly HideForbidden $hideForbidden,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $this->hideForbidden->renderResponse($next($request), $request, true);
        } catch (Throwable $exception) {
            $response = $this->hideForbidden->render($exception, $request, true);

            if ($response !== null) {
                return $response;
            }

            throw $exception;
        }
    }
}
