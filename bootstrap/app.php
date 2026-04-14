<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Ensure all API responses are JSON
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // All exceptions on API requests return JSON
        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        // 422 Validation errors
        $exceptions->render(function (ValidationException $e, Request $request): JsonResponse {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        });

        // 401 Unauthenticated
        $exceptions->render(function (AuthenticationException $e, Request $request): JsonResponse {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in.',
            ], 401);
        });

        // 403 Spatie permission / authorization
        $exceptions->render(function (UnauthorizedException $e, Request $request): JsonResponse {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        });

        // 404 Model / Route not found
        $exceptions->render(function (NotFoundHttpException $e, Request $request): JsonResponse {
            return response()->json([
                'success' => false,
                'message' => 'The requested resource was not found.',
            ], 404);
        });

    })->create();

