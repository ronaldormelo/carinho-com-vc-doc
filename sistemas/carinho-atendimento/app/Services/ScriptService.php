<?php

namespace App\Services;

use App\Support\DomainLookup;
use Illuminate\Support\Facades\DB;

class ScriptService
{
    public function __construct(
        private DomainLookup $domainLookup
    ) {
    }

    /**
     * Retorna todos os scripts ativos, opcionalmente filtrados por categoria e/ou nível de suporte
     */
    public function getScripts(?string $categoryCode = null, ?string $supportLevelCode = null): array
    {
        $query = DB::table('communication_scripts')
            ->join('domain_script_category', 'domain_script_category.id', '=', 'communication_scripts.category_id')
            ->leftJoin('domain_support_level', 'domain_support_level.id', '=', 'communication_scripts.support_level_id')
            ->where('communication_scripts.active', 1)
            ->select([
                'communication_scripts.id',
                'communication_scripts.code',
                'communication_scripts.title',
                'communication_scripts.body',
                'communication_scripts.variables_json',
                'communication_scripts.usage_hint',
                'domain_script_category.code as category_code',
                'domain_script_category.label as category_label',
                'domain_support_level.code as support_level_code',
                'domain_support_level.label as support_level_label',
            ]);

        if ($categoryCode) {
            $categoryId = $this->domainLookup->scriptCategoryId($categoryCode);
            $query->where('communication_scripts.category_id', $categoryId);
        }

        if ($supportLevelCode) {
            $supportLevelId = $this->domainLookup->supportLevelId($supportLevelCode);
            $query->where(function ($q) use ($supportLevelId) {
                $q->where('communication_scripts.support_level_id', $supportLevelId)
                  ->orWhereNull('communication_scripts.support_level_id');
            });
        }

        return $query->orderBy('communication_scripts.display_order')
            ->get()
            ->map(function ($script) {
                $script->variables = $script->variables_json ? json_decode($script->variables_json, true) : [];
                unset($script->variables_json);
                return $script;
            })
            ->toArray();
    }

    /**
     * Retorna um script específico pelo código
     */
    public function getScriptByCode(string $code): ?object
    {
        $script = DB::table('communication_scripts')
            ->join('domain_script_category', 'domain_script_category.id', '=', 'communication_scripts.category_id')
            ->leftJoin('domain_support_level', 'domain_support_level.id', '=', 'communication_scripts.support_level_id')
            ->where('communication_scripts.code', $code)
            ->where('communication_scripts.active', 1)
            ->select([
                'communication_scripts.id',
                'communication_scripts.code',
                'communication_scripts.title',
                'communication_scripts.body',
                'communication_scripts.variables_json',
                'communication_scripts.usage_hint',
                'domain_script_category.code as category_code',
                'domain_script_category.label as category_label',
                'domain_support_level.code as support_level_code',
                'domain_support_level.label as support_level_label',
            ])
            ->first();

        if ($script) {
            $script->variables = $script->variables_json ? json_decode($script->variables_json, true) : [];
            unset($script->variables_json);
        }

        return $script;
    }

    /**
     * Renderiza um script substituindo as variáveis pelos valores fornecidos
     */
    public function renderScript(string $code, array $variables = []): ?string
    {
        $script = $this->getScriptByCode($code);

        if (!$script) {
            return null;
        }

        $body = $script->body;

        foreach ($variables as $key => $value) {
            $body = str_replace('[' . strtoupper($key) . ']', $value, $body);
        }

        return $body;
    }

    /**
     * Retorna as categorias de scripts disponíveis
     */
    public function getCategories(): array
    {
        return DB::table('domain_script_category')
            ->select(['id', 'code', 'label'])
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    /**
     * Retorna scripts sugeridos com base no status da conversa
     */
    public function getSuggestedScripts(string $conversationStatusCode, ?string $supportLevelCode = null): array
    {
        // Mapeamento de status para categorias sugeridas
        $statusCategoryMap = [
            'new' => ['greeting'],
            'triage' => ['greeting', 'qualification'],
            'proposal' => ['proposal', 'objection'],
            'waiting' => ['objection', 'closing'],
            'active' => ['support', 'feedback'],
            'lost' => ['feedback'],
            'closed' => ['feedback'],
        ];

        $suggestedCategories = $statusCategoryMap[$conversationStatusCode] ?? ['support'];
        $scripts = [];

        foreach ($suggestedCategories as $categoryCode) {
            $categoryScripts = $this->getScripts($categoryCode, $supportLevelCode);
            $scripts = array_merge($scripts, $categoryScripts);
        }

        return $scripts;
    }

    /**
     * Busca scripts por termo
     */
    public function searchScripts(string $term): array
    {
        return DB::table('communication_scripts')
            ->join('domain_script_category', 'domain_script_category.id', '=', 'communication_scripts.category_id')
            ->leftJoin('domain_support_level', 'domain_support_level.id', '=', 'communication_scripts.support_level_id')
            ->where('communication_scripts.active', 1)
            ->where(function ($query) use ($term) {
                $query->where('communication_scripts.title', 'LIKE', "%{$term}%")
                      ->orWhere('communication_scripts.body', 'LIKE', "%{$term}%")
                      ->orWhere('communication_scripts.usage_hint', 'LIKE', "%{$term}%");
            })
            ->select([
                'communication_scripts.id',
                'communication_scripts.code',
                'communication_scripts.title',
                'communication_scripts.body',
                'communication_scripts.variables_json',
                'communication_scripts.usage_hint',
                'domain_script_category.code as category_code',
                'domain_script_category.label as category_label',
                'domain_support_level.code as support_level_code',
                'domain_support_level.label as support_level_label',
            ])
            ->orderBy('communication_scripts.display_order')
            ->get()
            ->map(function ($script) {
                $script->variables = $script->variables_json ? json_decode($script->variables_json, true) : [];
                unset($script->variables_json);
                return $script;
            })
            ->toArray();
    }
}
