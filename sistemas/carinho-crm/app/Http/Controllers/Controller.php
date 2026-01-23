<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Retorna resposta de sucesso padrão
     */
    protected function successResponse($data, string $message = 'Operação realizada com sucesso', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Retorna resposta de erro padrão
     */
    protected function errorResponse(string $message, int $code = 400, $errors = null)
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
     * Retorna resposta de recurso criado
     */
    protected function createdResponse($data, string $message = 'Recurso criado com sucesso')
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Retorna resposta de recurso não encontrado
     */
    protected function notFoundResponse(string $message = 'Recurso não encontrado')
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Retorna resposta de operação não autorizada
     */
    protected function unauthorizedResponse(string $message = 'Não autorizado')
    {
        return $this->errorResponse($message, 403);
    }
}
