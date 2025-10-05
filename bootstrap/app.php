<?php

use App\Http\Middleware\EnsureJsonResponseMiddleware;
use App\Http\Middleware\JwtRoleMiddleware;
use App\Http\Middleware\VerifyTokenStateMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('auth:jwt', [
            'auth:api',
            VerifyTokenStateMiddleware::class
        ]);
        $middleware->api(prepend: [EnsureJsonResponseMiddleware::class]);
        $middleware->alias(['role' => JwtRoleMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $exception) {
            return jsonResponse(
                status: 422,
                message: 'Validation errors',
                errors: $exception->errors()
            );
        });

        $exceptions->render(function (AuthenticationException $exception) {
            return jsonResponse(
                status: 401,
                message: $exception->getMessage() ?: 'Unauthenticated'
            );
        });

        $exceptions->render(function (NotFoundHttpException $exception) {
            return jsonResponse(
                status: 404,
                message: $exception->getMessage() ?: 'Resource not found'
            );
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
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => collect($exception->getTrace())->take(5)->toArray()
                ]
            );
        });
    })->create();
