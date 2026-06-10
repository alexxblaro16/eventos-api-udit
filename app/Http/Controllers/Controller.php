<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Controlador base. Como el enunciado pide que todas las respuestas tengan la misma
 * forma, pongo aqui dos helpers para no repetir el mismo array en cada metodo:
 *   { success, message, data, errors }
 */
abstract class Controller
{
    /**
     * Respuesta de éxito.
     */
    protected function jsonOk($data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    /**
     * Respuesta de error.
     */
    protected function jsonError(string $message, $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }
}
