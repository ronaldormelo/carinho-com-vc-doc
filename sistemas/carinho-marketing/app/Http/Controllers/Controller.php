<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Retorna resposta JSON padronizada de sucesso.
     */
    protected function success(mixed $data = null, string $message = 'Operacao realizada com sucesso', int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Retorna resposta JSON padronizada de erro.
     */
    protected function error(string $message = 'Erro na operacao', int $status = 400, array $errors = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Retorna resposta paginada padronizada.
     */
    protected function paginated(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator, string $message = 'Dados carregados com sucesso'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Retorna resposta de criacao.
     */
    protected function created(mixed $data = null, string $message = 'Recurso criado com sucesso'): \Illuminate\Http\JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Retorna resposta de nao encontrado.
     */
    protected function notFound(string $message = 'Recurso nao encontrado'): \Illuminate\Http\JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Retorna resposta de validacao.
     */
    protected function validationError(array $errors, string $message = 'Dados invalidos'): \Illuminate\Http\JsonResponse
    {
        return $this->error($message, 422, $errors);
    }
}
