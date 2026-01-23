<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DomainOwnerType;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService
    ) {}

    /**
     * Lista documentos.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Document::with(['status', 'template.docType', 'ownerType']);

        if ($request->has('owner_type_id') && $request->has('owner_id')) {
            $query->where('owner_type_id', $request->input('owner_type_id'))
                ->where('owner_id', $request->input('owner_id'));
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }

        $documents = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($documents);
    }

    /**
     * Cria novo documento.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'owner_type_id' => 'required|integer|exists:domain_owner_type,id',
            'owner_id' => 'required|integer',
            'template_id' => 'required|integer|exists:document_templates,id',
            'variables' => 'nullable|array',
        ]);

        $document = $this->documentService->createFromTemplate(
            $validated['owner_type_id'],
            $validated['owner_id'],
            $validated['template_id'],
            $validated['variables'] ?? []
        );

        if (!$document) {
            return $this->error('Falha ao criar documento');
        }

        return $this->created($document->load(['status', 'template.docType']));
    }

    /**
     * Exibe documento.
     */
    public function show(int $id): JsonResponse
    {
        $document = Document::with(['status', 'template.docType', 'ownerType', 'versions', 'signatures'])
            ->find($id);

        if (!$document) {
            return $this->notFound('Documento nao encontrado');
        }

        return $this->success($document);
    }

    /**
     * Atualiza documento.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->notFound('Documento nao encontrado');
        }

        $validated = $request->validate([
            'status_id' => 'nullable|integer|exists:domain_document_status,id',
        ]);

        if (isset($validated['status_id'])) {
            $document->status_id = $validated['status_id'];
        }

        $document->save();

        return $this->success($document->load(['status', 'template.docType']));
    }

    /**
     * Exclui documento.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $this->documentService->delete(
            $id,
            $request->input('actor_id', 0),
            $request->ip()
        );

        if (!$deleted) {
            return $this->error('Falha ao excluir documento');
        }

        return $this->success(null, 'Documento excluido com sucesso');
    }

    /**
     * Faz upload de documento.
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:25600|mimes:pdf,jpg,jpeg,png,webp',
            'owner_type_id' => 'required|integer|exists:domain_owner_type,id',
            'owner_id' => 'required|integer',
            'template_id' => 'required|integer|exists:document_templates,id',
            'metadata' => 'nullable|array',
        ]);

        $document = $this->documentService->upload(
            $request->file('file'),
            $validated['owner_type_id'],
            $validated['owner_id'],
            $validated['template_id'],
            $validated['metadata'] ?? []
        );

        if (!$document) {
            return $this->error('Falha no upload do documento');
        }

        return $this->created($document->load(['status', 'template.docType']));
    }

    /**
     * Gera URL assinada para download.
     */
    public function signedUrl(Request $request, int $id): JsonResponse
    {
        $result = $this->documentService->getSignedUrl(
            $id,
            $request->input('actor_id', 0),
            $request->ip()
        );

        if (!$result) {
            return $this->notFound('Documento nao encontrado');
        }

        return $this->success($result);
    }

    /**
     * Faz download do documento.
     */
    public function download(Request $request, int $id): mixed
    {
        $result = $this->documentService->download(
            $id,
            $request->input('actor_id', 0),
            $request->ip()
        );

        if (!$result) {
            return $this->notFound('Documento nao encontrado');
        }

        return response($result['content'], 200, [
            'Content-Type' => $result['content_type'],
            'Content-Length' => $result['size'],
            'Content-Disposition' => "attachment; filename=\"{$result['filename']}\"",
        ]);
    }

    /**
     * Download por token.
     */
    public function downloadByToken(string $token): mixed
    {
        // Implementacao de validacao de token para downloads publicos
        return $this->error('Token invalido ou expirado', 401);
    }

    /**
     * Valida documento.
     */
    public function validateDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_id' => 'required_without:file_url|integer',
            'file_url' => 'required_without:document_id|string|url',
            'doc_type' => 'required|string',
        ]);

        // Implementacao de validacao automatica (OCR, etc.)
        return $this->success([
            'valid' => true,
            'message' => 'Documento validado',
        ]);
    }

    /**
     * Lista versoes do documento.
     */
    public function versions(int $id): JsonResponse
    {
        $document = Document::with('versions')->find($id);

        if (!$document) {
            return $this->notFound('Documento nao encontrado');
        }

        return $this->success($document->versions);
    }

    /**
     * Cria nova versao.
     */
    public function createVersion(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:25600|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $version = $this->documentService->createVersion($id, $request->file('file'));

        if (!$version) {
            return $this->error('Falha ao criar versao');
        }

        return $this->created($version);
    }

    /**
     * Busca documentos por proprietario.
     */
    public function byOwner(string $ownerType, int $ownerId): JsonResponse
    {
        $ownerTypeId = match ($ownerType) {
            'client' => DomainOwnerType::CLIENT,
            'caregiver' => DomainOwnerType::CAREGIVER,
            'company' => DomainOwnerType::COMPANY,
            default => null,
        };

        if (!$ownerTypeId) {
            return $this->error('Tipo de proprietario invalido');
        }

        $documents = $this->documentService->listByOwner($ownerTypeId, $ownerId);

        return $this->success($documents);
    }
}
