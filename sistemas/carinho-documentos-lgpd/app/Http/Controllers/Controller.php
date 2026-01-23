<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Retorna resposta de sucesso.
     */
    protected function success(mixed $data = null, string $message = 'Sucesso', int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'ok' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Retorna resposta de erro.
     */
    protected function error(string $message = 'Erro', int $status = 400, array $errors = []): \Illuminate\Http\JsonResponse
    {
        $response = [
            'ok' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Retorna resposta de nao encontrado.
     */
    protected function notFound(string $message = 'Recurso nao encontrado'): \Illuminate\Http\JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Retorna resposta de criado.
     */
    protected function created(mixed $data = null, string $message = 'Criado com sucesso'): \Illuminate\Http\JsonResponse
    {
        return $this->success($data, $message, 201);
    }
}
