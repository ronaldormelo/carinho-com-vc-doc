<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Retorna resposta de sucesso padronizada.
     */
    protected function success(mixed $data = null, string $message = 'Success', int $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Retorna resposta de erro padronizada.
     */
    protected function error(string $message, int $code = 400, ?array $errors = null): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Retorna resposta de item nao encontrado.
     */
    protected function notFound(string $message = 'Resource not found'): \Illuminate\Http\JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Retorna resposta de validacao.
     */
    protected function validationError(array $errors): \Illuminate\Http\JsonResponse
    {
        return $this->error('Validation failed', 422, $errors);
    }
}
