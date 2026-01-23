<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverDocument;
use App\Models\DomainDocumentStatus;
use App\Models\DomainDocumentType;
use App\Services\DocumentValidationService;
use App\Jobs\ProcessDocumentValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentValidationService $validationService
    ) {}

    /**
     * Lista documentos de um cuidador.
     */
    public function index(int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $documents = $caregiver->documents()
            ->with(['docType', 'status'])
            ->get();

        $requiredTypes = DomainDocumentType::required();
        $optionalTypes = DomainDocumentType::optional();

        return $this->success([
            'documents' => $documents,
            'required_types' => $requiredTypes,
            'optional_types' => $optionalTypes,
            'missing_required' => $this->validationService->getMissingRequiredDocuments($caregiver),
        ]);
    }

    /**
     * Upload de documento.
     */
    public function store(Request $request, int $caregiverId): JsonResponse
    {
        $caregiver = Caregiver::find($caregiverId);

        if (!$caregiver) {
            return $this->error('Cuidador nao encontrado', 404);
        }

        $validator = Validator::make($request->all(), [
            'doc_type_code' => 'required|string|exists:domain_document_type,code',
            'file' => 'required|file|max:' . (config('cuidadores.triagem.max_file_size_mb') * 1024),
        ]);

        if ($validator->fails()) {
            return $this->error('Dados invalidos', 422, $validator->errors()->toArray());
        }

        // Validar extensao
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = config('cuidadores.triagem.allowed_extensions', []);

        if (!in_array($extension, $allowedExtensions)) {
            return $this->error('Extensao de arquivo nao permitida', 422, [
                'allowed' => $allowedExtensions,
            ]);
        }

        $docType = DomainDocumentType::byCode($request->get('doc_type_code'));
        $pendingStatus = DomainDocumentStatus::pending();

        // Fazer upload (integrar com sistema de documentos)
        $fileUrl = $this->validationService->uploadDocument($caregiver, $file);

        $document = CaregiverDocument::create([
            'caregiver_id' => $caregiver->id,
            'doc_type_id' => $docType->id,
            'file_url' => $fileUrl,
            'status_id' => $pendingStatus->id,
        ]);

        // Dispara job para validacao assincrona
        ProcessDocumentValidation::dispatch($document);

        return $this->success(
            $document->load(['docType', 'status']),
            'Documento enviado para validacao',
            201
        );
    }

    /**
     * Exibe documento especifico.
     */
    public function show(int $caregiverId, int $documentId): JsonResponse
    {
        $document = CaregiverDocument::where('caregiver_id', $caregiverId)
            ->where('id', $documentId)
            ->with(['docType', 'status'])
            ->first();

        if (!$document) {
            return $this->error('Documento nao encontrado', 404);
        }

        return $this->success($document);
    }

    /**
     * Aprova documento (validacao manual).
     */
    public function approve(int $caregiverId, int $documentId): JsonResponse
    {
        $document = CaregiverDocument::where('caregiver_id', $caregiverId)
            ->where('id', $documentId)
            ->first();

        if (!$document) {
            return $this->error('Documento nao encontrado', 404);
        }

        $document->update([
            'status_id' => DomainDocumentStatus::VERIFIED,
            'verified_at' => now(),
        ]);

        return $this->success(
            $document->fresh(['docType', 'status']),
            'Documento aprovado com sucesso'
        );
    }

    /**
     * Rejeita documento.
     */
    public function reject(Request $request, int $caregiverId, int $documentId): JsonResponse
    {
        $document = CaregiverDocument::where('caregiver_id', $caregiverId)
            ->where('id', $documentId)
            ->first();

        if (!$document) {
            return $this->error('Documento nao encontrado', 404);
        }

        $document->update([
            'status_id' => DomainDocumentStatus::REJECTED,
        ]);

        // Notificar cuidador sobre rejeicao
        $this->validationService->notifyDocumentRejected($document, $request->get('reason'));

        return $this->success(
            $document->fresh(['docType', 'status']),
            'Documento rejeitado'
        );
    }

    /**
     * Lista documentos pendentes de validacao (para admin).
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = min(
            (int) $request->get('per_page', 20),
            100
        );

        $documents = CaregiverDocument::pending()
            ->with(['caregiver', 'docType', 'status'])
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        return $this->paginated($documents, 'Documentos pendentes carregados');
    }
}
