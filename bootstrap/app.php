<?php

use App\Http\Middleware\EnsureJsonResponseMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [EnsureJsonResponseMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->expectsJson();
        });

        $exceptions->render(function (ValidationException $exception) {
            return jsonResponse(
                status: 422,
                message: 'Validation errors',
                errors: $exception->errors()
            );
        });

        $exceptions->render(function (AuthenticationException $exception) {
            return jsonResponse(status: 401, message: $exception->getMessage());
        });

        $exceptions->render(function (Throwable $exception) {
            if (app()->environment('production')) {
                return jsonResponse(
                    status: 500,
                    message: 'Internal Server Error'
                );
            }

            return jsonResponse(
                status: 500,
                message: $exception->getMessage(),
                errors: [
                    'exception' => class_basename($exception),
                    'trace' => $exception->getTrace(),
                ]
            );
        });
    })->create();
