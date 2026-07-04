<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response as AccessResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DenyingHideForbiddenFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

beforeEach(function (): void {
    config()->set('hide-forbidden.enabled', true);
    config()->set('hide-forbidden.mode', 'all');
    config()->set('hide-forbidden.only_routes', []);
    config()->set('hide-forbidden.except_routes', []);
    config()->set('hide-forbidden.only_paths', []);
    config()->set('hide-forbidden.except_paths', []);
    config()->set('hide-forbidden.guards', []);
    config()->set('hide-forbidden.log_original_403', false);
});

function secretRoute(Closure $action): Illuminate\Routing\Route
{
    return Route::get('/secret', $action)->name('secret');
}

test('laravel exception based forbidden routes become not found responses', function (Closure $registerRoute): void {
    $registerRoute();

    $this->get('/secret')->assertNotFound();
})->with([
    'abort helper' => [
        fn () => secretRoute(fn () => abort(403)),
    ],
    'abort_if helper' => [
        fn () => secretRoute(fn () => abort_if(true, 403)),
    ],
    'abort_unless helper' => [
        fn () => secretRoute(fn () => abort_unless(false, 403)),
    ],
    'http exception' => [
        fn () => secretRoute(fn () => throw new HttpException(403)),
    ],
    'access denied http exception' => [
        fn () => secretRoute(fn () => throw new AccessDeniedHttpException),
    ],
    'authorization exception' => [
        fn () => secretRoute(fn () => throw new AuthorizationException),
    ],
    'authorization exception with status' => [
        fn () => secretRoute(fn () => throw (new AuthorizationException)->withStatus(403)),
    ],
    'gate boolean denial' => [
        function (): void {
            Gate::define('hide-forbidden-denied', fn (?object $user = null): bool => false);

            secretRoute(fn () => Gate::authorize('hide-forbidden-denied'));
        },
    ],
    'gate response denial' => [
        function (): void {
            Gate::define(
                'hide-forbidden-denied-with-status',
                fn (?object $user = null): AccessResponse => AccessResponse::denyWithStatus(403),
            );

            secretRoute(fn () => Gate::authorize('hide-forbidden-denied-with-status'));
        },
    ],
    'access response denial' => [
        fn () => secretRoute(fn () => AccessResponse::deny()->authorize()),
    ],
    'access response denial with status' => [
        fn () => secretRoute(fn () => AccessResponse::denyWithStatus(403)->authorize()),
    ],
    'can middleware denial' => [
        function (): void {
            Gate::define('hide-forbidden-can-denied', fn (?object $user = null): bool => false);

            secretRoute(fn () => 'ok')
                ->middleware('can:hide-forbidden-can-denied')
                ->name('secret');
        },
    ],
    'form request authorization denial' => [
        fn () => secretRoute(fn (DenyingHideForbiddenFormRequest $request) => 'ok'),
    ],
]);

test('middleware converts laravel forbidden response routes to not found responses', function (Closure $registerRoute): void {
    config()->set('hide-forbidden.mode', 'middleware');

    $registerRoute();

    $this->get('/secret')->assertNotFound();
})->with([
    'response helper' => [
        fn () => secretRoute(fn () => response('Forbidden', 403))
            ->middleware('hide-forbidden')
            ->name('secret'),
    ],
    'abort helper with response' => [
        fn () => secretRoute(fn () => abort(response('Forbidden', 403)))
            ->middleware('hide-forbidden')
            ->name('secret'),
    ],
    'json response helper' => [
        fn () => secretRoute(fn () => response()->json(['message' => 'Forbidden'], 403))
            ->middleware('hide-forbidden')
            ->name('secret'),
    ],
    'no content response helper' => [
        fn () => secretRoute(fn () => response()->noContent(403))
            ->middleware('hide-forbidden')
            ->name('secret'),
    ],
    'illuminate response instance' => [
        fn () => secretRoute(fn () => new IlluminateResponse('Forbidden', 403))
            ->middleware('hide-forbidden')
            ->name('secret'),
    ],
    'json response instance' => [
        fn () => secretRoute(fn () => new JsonResponse(['message' => 'Forbidden'], 403))
            ->middleware('hide-forbidden')
            ->name('secret'),
    ],
    'symfony response instance' => [
        fn () => secretRoute(fn () => new SymfonyResponse('Forbidden', 403))
            ->middleware('hide-forbidden')
            ->name('secret'),
    ],
]);

test('normal not found responses stay not found', function (): void {
    $this->get('/missing')->assertNotFound();
});

test('server errors are not converted', function (): void {
    Route::get('/broken', fn () => throw new HttpException(500))->name('broken');

    $this->withoutExceptionHandling();

    $this->get('/broken');
})->throws(HttpException::class);

test('disabled configuration keeps forbidden responses', function (): void {
    config()->set('hide-forbidden.enabled', false);

    secretRoute(fn () => throw new HttpException(403));

    $this->get('/secret')->assertForbidden();
});

test('default middleware mode does not convert globally', function (): void {
    config()->set('hide-forbidden.mode', 'middleware');

    secretRoute(fn () => throw new HttpException(403));

    $this->get('/secret')->assertForbidden();
});

test('except routes keep forbidden responses', function (): void {
    config()->set('hide-forbidden.except_routes', ['secret']);

    secretRoute(fn () => throw new HttpException(403));

    $this->get('/secret')->assertForbidden();
});

test('only routes convert matching named routes', function (): void {
    config()->set('hide-forbidden.mode', 'routes');
    config()->set('hide-forbidden.only_routes', ['admin.*']);

    Route::get('/admin/secret', fn () => throw new HttpException(403))->name('admin.secret');
    Route::get('/profile', fn () => throw new HttpException(403))->name('profile');

    $this->get('/admin/secret')->assertNotFound();
    $this->get('/profile')->assertForbidden();
});

test('only paths convert matching request paths', function (): void {
    config()->set('hide-forbidden.mode', 'paths');
    config()->set('hide-forbidden.only_paths', ['admin/*']);

    Route::get('/admin/secret', fn () => throw new HttpException(403))->name('admin.secret');
    Route::get('/profile', fn () => throw new HttpException(403))->name('profile');

    $this->get('/admin/secret')->assertNotFound();
    $this->get('/profile')->assertForbidden();
});

test('json requests receive configured not found payload', function (): void {
    config()->set('hide-forbidden.api_response', ['message' => 'Hidden']);

    Route::get('/api/secret', fn () => throw new HttpException(403))->name('api.secret');

    $this->getJson('/api/secret')
        ->assertNotFound()
        ->assertExactJson(['message' => 'Hidden']);
});

test('middleware converts wrapped routes in middleware mode', function (): void {
    config()->set('hide-forbidden.mode', 'middleware');

    secretRoute(fn () => throw new HttpException(403))
        ->middleware('hide-forbidden')
        ->name('secret');

    $this->get('/secret')->assertNotFound();
});

test('middleware does not convert unwrapped routes in middleware mode', function (): void {
    config()->set('hide-forbidden.mode', 'middleware');

    secretRoute(fn () => throw new HttpException(403));

    $this->get('/secret')->assertForbidden();
});

test('original forbidden response can be logged', function (): void {
    config()->set('hide-forbidden.log_original_403', true);

    Log::spy();

    secretRoute(fn () => throw new HttpException(403));

    $this->get('/secret')->assertNotFound();

    Log::shouldHaveReceived('notice')->once();
});
