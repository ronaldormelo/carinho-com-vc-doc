<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\DomainDocType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentTemplateController extends Controller
{
    /**
     * Lista templates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DocumentTemplate::with('docType');

        if ($request->boolean('active_only', true)) {
            $query->where('active', true);
        }

        if ($request->has('doc_type_id')) {
            $query->where('doc_type_id', $request->input('doc_type_id'));
        }

        $templates = $query->orderBy('doc_type_id')
            ->orderBy('version', 'desc')
            ->get();

        return $this->success($templates);
    }

    /**
     * Cria template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doc_type_id' => 'required|integer|exists:domain_doc_type,id',
            'version' => 'required|string|max:32',
            'content' => 'required|string',
            'active' => 'nullable|boolean',
        ]);

        $template = DocumentTemplate::create([
            'doc_type_id' => $validated['doc_type_id'],
            'version' => $validated['version'],
            'content' => $validated['content'],
            'active' => $validated['active'] ?? false,
        ]);

        // Se ativo, desativa outros do mesmo tipo
        if ($template->active) {
            $template->activate();
        }

        return $this->created($template->load('docType'));
    }

    /**
     * Exibe template.
     */
    public function show(int $id): JsonResponse
    {
        $template = DocumentTemplate::with('docType')->find($id);

        if (!$template) {
            return $this->notFound('Template nao encontrado');
        }

        return $this->success($template);
    }

    /**
     * Atualiza template.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = DocumentTemplate::find($id);

        if (!$template) {
            return $this->notFound('Template nao encontrado');
        }

        $validated = $request->validate([
            'version' => 'nullable|string|max:32',
            'content' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);

        if (isset($validated['version'])) {
            $template->version = $validated['version'];
        }

        if (isset($validated['content'])) {
            $template->content = $validated['content'];
        }

        if (isset($validated['active'])) {
            if ($validated['active']) {
                $template->activate();
            } else {
                $template->deactivate();
            }
        } else {
            $template->save();
        }

        return $this->success($template->load('docType'));
    }

    /**
     * Exclui template.
     */
    public function destroy(int $id): JsonResponse
    {
        $template = DocumentTemplate::find($id);

        if (!$template) {
            return $this->notFound('Template nao encontrado');
        }

        // Verifica se tem documentos associados
        if ($template->documents()->exists()) {
            return $this->error('Template possui documentos associados');
        }

        $template->delete();

        return $this->success(null, 'Template excluido');
    }

    /**
     * Templates por tipo.
     */
    public function byType(string $type): JsonResponse
    {
        $docType = DomainDocType::findByCode($type);

        if (!$docType) {
            return $this->error('Tipo de documento invalido');
        }

        $template = DocumentTemplate::getActiveByType($docType->id);

        if (!$template) {
            return $this->notFound('Template nao encontrado');
        }

        return $this->success($template);
    }

    /**
     * Renderiza template com variaveis.
     */
    public function render(Request $request, int $id): JsonResponse
    {
        $template = DocumentTemplate::find($id);

        if (!$template) {
            return $this->notFound('Template nao encontrado');
        }

        $validated = $request->validate([
            'variables' => 'required|array',
        ]);

        $rendered = $template->render($validated['variables']);

        return $this->success([
            'template_id' => $template->id,
            'version' => $template->version,
            'rendered_content' => $rendered,
        ]);
    }

    /**
     * Termos de uso publicos.
     */
    public function publicTerms(): JsonResponse
    {
        $template = DocumentTemplate::getActiveByType(DomainDocType::TERMOS);

        if (!$template) {
            return $this->notFound('Termos nao encontrados');
        }

        $rendered = $template->render([
            'data_atualizacao' => now()->format('d/m/Y'),
        ]);

        return $this->success([
            'version' => $template->version,
            'content' => $rendered,
        ]);
    }

    /**
     * Politica de privacidade publica.
     */
    public function publicPrivacy(): JsonResponse
    {
        $template = DocumentTemplate::getActiveByType(DomainDocType::PRIVACIDADE);

        if (!$template) {
            return $this->notFound('Politica de privacidade nao encontrada');
        }

        $rendered = $template->render([
            'data_atualizacao' => now()->format('d/m/Y'),
        ]);

        return $this->success([
            'version' => $template->version,
            'content' => $rendered,
        ]);
    }
}
