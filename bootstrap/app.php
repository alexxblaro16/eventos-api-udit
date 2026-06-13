<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /**
         * Para que los errores salgan con la misma estructura que el resto
         * { success, message, data, errors } y con su codigo HTTP. Solo en /api.
         */
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null; // fuera de la api lo dejo por defecto
            }

            // Errores de validación -> 422
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los datos enviados no son válidos.',
                    'data' => null,
                    'errors' => $e->errors(),
                ], 422);
            }

            // No autenticado (falta token) -> 401
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado.',
                    'data' => null,
                    'errors' => null,
                ], 401);
            }

            // Sin permiso (Policy) -> 403
            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'No autorizado.',
                    'data' => null,
                    'errors' => null,
                ], 403);
            }

            // Recurso no encontrado -> 404
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso no encontrado.',
                    'data' => null,
                    'errors' => null,
                ], 404);
            }

            return null; // el resto se gestiona por defecto
        });
    })->create();
