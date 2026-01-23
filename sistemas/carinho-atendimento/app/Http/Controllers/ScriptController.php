<?php

namespace App\Http\Controllers;

use App\Services\ScriptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScriptController extends Controller
{
    public function __construct(
        private ScriptService $scriptService
    ) {
    }

    /**
     * Lista todos os scripts, com filtros opcionais
     */
    public function index(Request $request): JsonResponse
    {
        $categoryCode = $request->input('category');
        $supportLevelCode = $request->input('support_level');

        $scripts = $this->scriptService->getScripts($categoryCode, $supportLevelCode);

        return response()->json(['scripts' => $scripts]);
    }

    /**
     * Retorna as categorias de scripts
     */
    public function categories(): JsonResponse
    {
        $categories = $this->scriptService->getCategories();
        return response()->json(['categories' => $categories]);
    }

    /**
     * Busca scripts por termo
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->input('q', '');

        if (strlen($term) < 2) {
            return response()->json(['scripts' => []]);
        }

        $scripts = $this->scriptService->searchScripts($term);

        return response()->json(['scripts' => $scripts]);
    }

    /**
     * Retorna um script específico pelo código
     */
    public function show(string $code): JsonResponse
    {
        $script = $this->scriptService->getScriptByCode($code);

        if (!$script) {
            return response()->json(['message' => 'Script not found'], 404);
        }

        return response()->json(['script' => $script]);
    }

    /**
     * Renderiza um script com as variáveis fornecidas
     */
    public function render(Request $request, string $code): JsonResponse
    {
        $variables = $request->input('variables', []);

        $rendered = $this->scriptService->renderScript($code, $variables);

        if ($rendered === null) {
            return response()->json(['message' => 'Script not found'], 404);
        }

        return response()->json(['rendered' => $rendered]);
    }
}
