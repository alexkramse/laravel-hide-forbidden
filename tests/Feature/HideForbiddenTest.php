<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

test('authorization exceptions become not found responses', function (): void {
    Route::get('/secret', fn () => throw new AuthorizationException)->name('secret');

    $this->get('/secret')->assertNotFound();
});

test('access denied http exceptions become not found responses', function (): void {
    Route::get('/secret', fn () => throw new AccessDeniedHttpException)->name('secret');

    $this->get('/secret')->assertNotFound();
});

test('configured forbidden status codes become not found responses', function (): void {
    Route::get('/secret', fn () => throw new HttpException(403))->name('secret');

    $this->get('/secret')->assertNotFound();
});

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

    Route::get('/secret', fn () => throw new HttpException(403))->name('secret');

    $this->get('/secret')->assertForbidden();
});

test('default middleware mode does not convert globally', function (): void {
    config()->set('hide-forbidden.mode', 'middleware');

    Route::get('/secret', fn () => throw new HttpException(403))->name('secret');

    $this->get('/secret')->assertForbidden();
});

test('except routes keep forbidden responses', function (): void {
    config()->set('hide-forbidden.except_routes', ['secret']);

    Route::get('/secret', fn () => throw new HttpException(403))->name('secret');

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

    Route::get('/secret', fn () => throw new HttpException(403))
        ->middleware('hide-forbidden')
        ->name('secret');

    $this->get('/secret')->assertNotFound();
});

test('middleware does not convert unwrapped routes in middleware mode', function (): void {
    config()->set('hide-forbidden.mode', 'middleware');

    Route::get('/secret', fn () => throw new HttpException(403))->name('secret');

    $this->get('/secret')->assertForbidden();
});

test('original forbidden response can be logged', function (): void {
    config()->set('hide-forbidden.log_original_403', true);

    Log::spy();

    Route::get('/secret', fn () => throw new HttpException(403))->name('secret');

    $this->get('/secret')->assertNotFound();

    Log::shouldHaveReceived('notice')->once();
});
