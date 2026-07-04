<?php

namespace Alexkramse\LaravelHideForbidden;

use Alexkramse\LaravelHideForbidden\Support\RequestMatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class HideForbidden
{
    public function __construct(
        private readonly RequestMatcher $requestMatcher,
        private readonly ExceptionHandler $exceptionHandler,
    ) {}

    public function render(Throwable $exception, Request $request, bool $force = false): ?SymfonyResponse
    {
        if (! $this->requestMatcher->shouldHide($exception, $request, $force)) {
            return null;
        }

        $this->logHiddenForbidden($request, $exception::class);

        return $this->notFoundResponse($request);
    }

    public function renderResponse(SymfonyResponse $response, Request $request, bool $force = false): SymfonyResponse
    {
        if (! $this->requestMatcher->shouldHideResponse($response, $request, $force)) {
            return $response;
        }

        $this->logHiddenForbidden($request);

        return $this->notFoundResponse($request, $response);
    }

    private function notFoundResponse(Request $request, ?SymfonyResponse $response = null): SymfonyResponse
    {
        if ($this->shouldReturnJsonNotFound($request, $response)) {
            return response()->json(
                (array) config('hide-forbidden.api_response', ['message' => 'Not Found']),
                SymfonyResponse::HTTP_NOT_FOUND,
            );
        }

        return $this->exceptionHandler->render($request, new NotFoundHttpException);
    }

    private function shouldReturnJsonNotFound(Request $request, ?SymfonyResponse $response): bool
    {
        if ($request->expectsJson() || $response instanceof JsonResponse) {
            return true;
        }

        $contentType = $response?->headers->get('Content-Type');

        return is_string($contentType)
            && (str_contains($contentType, '/json') || str_contains($contentType, '+json'));
    }

    private function logHiddenForbidden(Request $request, ?string $exception = null): void
    {
        if (! (bool) config('hide-forbidden.log_original_403', false)) {
            return;
        }

        Log::notice('Forbidden response hidden as not found.', [
            'exception' => $exception,
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
        ]);
    }
}
