<?php

namespace Alexkramse\LaravelHideForbidden;

use Alexkramse\LaravelHideForbidden\Support\RequestMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class HideForbidden
{
    public function __construct(
        private readonly RequestMatcher $requestMatcher,
    ) {}

    public function render(Throwable $exception, Request $request, bool $force = false): JsonResponse|Response|null
    {
        if (! $this->requestMatcher->shouldHide($exception, $request, $force)) {
            return null;
        }

        $this->logHiddenForbidden($request, $exception::class);

        return $this->notFoundResponse($request);
    }

    public function renderResponse(SymfonyResponse $response, Request $request, bool $force = false): JsonResponse|Response|SymfonyResponse
    {
        if (! $this->requestMatcher->shouldHideResponse($response, $request, $force)) {
            return $response;
        }

        $this->logHiddenForbidden($request);

        return $this->notFoundResponse($request);
    }

    private function notFoundResponse(Request $request): JsonResponse|Response
    {

        if ($request->expectsJson()) {
            return response()->json(
                (array) config('hide-forbidden.api_response', ['message' => 'Not Found']),
                SymfonyResponse::HTTP_NOT_FOUND,
            );
        }

        return response('', SymfonyResponse::HTTP_NOT_FOUND);
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
