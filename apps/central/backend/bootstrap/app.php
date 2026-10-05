<?php

use App\Exceptions\InvalidEditorialTransitionException;
use App\Http\Middleware\RequestId;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/health',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool =>
                $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (
            ValidationException $exception,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                code: 'VALIDATION_FAILED',
                message: 'Datele trimise nu sunt valide.',
                status: 422,
                details: [
                    'fields' => $exception->errors(),
                ],
            );
        });

        $exceptions->render(function (
            AuthenticationException $exception,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                code: 'UNAUTHENTICATED',
                message: 'Autentificarea este necesară.',
                status: 401,
            );
        });

        $exceptions->render(function (
            AccessDeniedHttpException $exception,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                code: 'FORBIDDEN',
                message: 'Nu aveți permisiunea necesară pentru această acțiune.',
                status: 403,
            );
        });

        $exceptions->render(function (
            NotFoundHttpException $exception,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                code: 'RESOURCE_NOT_FOUND',
                message: 'Resursa solicitată nu a fost găsită.',
                status: 404,
            );
        });

        $exceptions->render(function (
            TooManyRequestsHttpException $exception,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                code: 'RATE_LIMIT_EXCEEDED',
                message: 'Au fost trimise prea multe cereri. Încercați din nou mai târziu.',
                status: 429,
                headers: $exception->getHeaders(),
            );
        });


$exceptions->render(function (
    InvalidEditorialTransitionException $exception,
    Request $request
) {
    if (! $request->is('api/*')) {
        return null;
    }

    return ApiResponse::error(
        code: 'INVALID_EDITORIAL_TRANSITION',
        message: $exception->getMessage(),
        status: 409,
    );
});


        $exceptions->render(function (
            \Throwable $exception,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                code: 'INTERNAL_ERROR',
                message: 'A apărut o eroare internă.',
                status: 500,
            );
        });
    })
    ->create();
