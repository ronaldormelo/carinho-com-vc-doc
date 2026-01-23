<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\RetentionPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RetentionPolicyController extends Controller
{
    /**
     * Lista politicas de retencao.
     */
    public function index(): JsonResponse
    {
        $policies = RetentionPolicy::with('docType')->get();

        return $this->success($policies);
    }

    /**
     * Cria politica.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doc_type_id' => 'required|integer|exists:domain_doc_type,id|unique:retention_policies,doc_type_id',
            'retention_days' => 'required|integer|min:1',
        ]);

        $policy = RetentionPolicy::create($validated);

        return $this->created($policy->load('docType'));
    }

    /**
     * Exibe politica.
     */
    public function show(int $id): JsonResponse
    {
        $policy = RetentionPolicy::with('docType')->find($id);

        if (!$policy) {
            return $this->notFound('Politica nao encontrada');
        }

        return $this->success($policy);
    }

    /**
     * Atualiza politica.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $policy = RetentionPolicy::find($id);

        if (!$policy) {
            return $this->notFound('Politica nao encontrada');
        }

        $validated = $request->validate([
            'retention_days' => 'required|integer|min:1',
        ]);

        $policy->retention_days = $validated['retention_days'];
        $policy->save();

        return $this->success($policy->load('docType'));
    }

    /**
     * Exclui politica.
     */
    public function destroy(int $id): JsonResponse
    {
        $policy = RetentionPolicy::find($id);

        if (!$policy) {
            return $this->notFound('Politica nao encontrada');
        }

        $policy->delete();

        return $this->success(null, 'Politica excluida');
    }

    /**
     * Executa politicas de retencao.
     */
    public function execute(): JsonResponse
    {
        $policies = RetentionPolicy::with('docType')->get();
        $results = [];

        foreach ($policies as $policy) {
            $expirationDate = now()->subDays($policy->retention_days);

            $expiredDocuments = Document::whereHas('template', function ($query) use ($policy) {
                $query->where('doc_type_id', $policy->doc_type_id);
            })
                ->where('created_at', '<', $expirationDate)
                ->where('status_id', '!=', 3) // Nao arquivados
                ->count();

            $results[] = [
                'doc_type' => $policy->docType->code,
                'retention_days' => $policy->retention_days,
                'expired_count' => $expiredDocuments,
            ];
        }

        return $this->success([
            'executed_at' => now()->toIso8601String(),
            'policies' => $results,
        ]);
    }

    /**
     * Lista documentos pendentes de retencao.
     */
    public function pending(): JsonResponse
    {
        $policies = RetentionPolicy::with('docType')->get();
        $pending = [];

        foreach ($policies as $policy) {
            $expirationDate = now()->subDays($policy->retention_days);

            $documents = Document::whereHas('template', function ($query) use ($policy) {
                $query->where('doc_type_id', $policy->doc_type_id);
            })
                ->where('created_at', '<', $expirationDate)
                ->where('status_id', '!=', 3)
                ->select('id', 'owner_type_id', 'owner_id', 'created_at')
                ->limit(100)
                ->get();

            if ($documents->isNotEmpty()) {
                $pending[] = [
                    'doc_type' => $policy->docType->code,
                    'documents' => $documents->toArray(),
                ];
            }
        }

        return $this->success($pending);
    }
}
