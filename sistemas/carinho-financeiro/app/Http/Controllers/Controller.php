<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Retorna resposta de sucesso.
     */
    protected function successResponse($data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Retorna resposta de criação.
     */
    protected function createdResponse($data = null, ?string $message = null): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Retorna resposta de erro.
     */
    protected function errorResponse(string $message, int $code = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Retorna resposta de não encontrado.
     */
    protected function notFoundResponse(string $message = 'Recurso não encontrado'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Retorna resposta de validação.
     */
    protected function validationResponse(array $errors): JsonResponse
    {
        return $this->errorResponse('Dados inválidos', 422, $errors);
    }
}
