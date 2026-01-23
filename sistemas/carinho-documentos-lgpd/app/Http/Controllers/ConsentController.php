<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use App\Models\DomainConsentSubjectType;
use App\Services\ConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    public function __construct(
        private ConsentService $consentService
    ) {}

    /**
     * Lista consentimentos.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Consent::with('subjectType');

        if ($request->has('subject_type_id') && $request->has('subject_id')) {
            $query->where('subject_type_id', $request->input('subject_type_id'))
                ->where('subject_id', $request->input('subject_id'));
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $consents = $query->orderBy('granted_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($consents);
    }

    /**
     * Registra consentimento.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => 'required|string|in:client,caregiver',
            'subject_id' => 'required|integer',
            'consent_type' => 'required|string|in:' . implode(',', array_keys(Consent::TYPES)),
            'source' => 'required|string|in:' . implode(',', [
                Consent::SOURCE_WEBSITE,
                Consent::SOURCE_APP,
                Consent::SOURCE_WHATSAPP,
                Consent::SOURCE_CONTRACT,
            ]),
        ]);

        $subjectTypeId = match ($validated['subject_type']) {
            'client' => DomainConsentSubjectType::CLIENT,
            'caregiver' => DomainConsentSubjectType::CAREGIVER,
            default => DomainConsentSubjectType::CLIENT,
        };

        $consent = $this->consentService->grant(
            $subjectTypeId,
            $validated['subject_id'],
            $validated['consent_type'],
            $validated['source']
        );

        if (!$consent) {
            return $this->error('Falha ao registrar consentimento');
        }

        return $this->created([
            'id' => $consent->id,
            'consent_type' => $consent->consent_type,
            'granted_at' => $consent->granted_at->toIso8601String(),
            'source' => $consent->source,
        ]);
    }

    /**
     * Exibe consentimento.
     */
    public function show(int $id): JsonResponse
    {
        $consent = Consent::with('subjectType')->find($id);

        if (!$consent) {
            return $this->notFound('Consentimento nao encontrado');
        }

        return $this->success([
            'id' => $consent->id,
            'subject_type' => $consent->subjectType->code,
            'subject_id' => $consent->subject_id,
            'consent_type' => $consent->consent_type,
            'granted_at' => $consent->granted_at->toIso8601String(),
            'source' => $consent->source,
            'revoked_at' => $consent->revoked_at?->toIso8601String(),
            'is_active' => $consent->isActive(),
        ]);
    }

    /**
     * Revoga consentimento.
     */
    public function revoke(int $id): JsonResponse
    {
        $revoked = $this->consentService->revoke($id);

        if (!$revoked) {
            return $this->error('Falha ao revogar consentimento');
        }

        return $this->success(null, 'Consentimento revogado');
    }

    /**
     * Lista consentimentos por titular.
     */
    public function bySubject(string $subjectType, int $subjectId): JsonResponse
    {
        $subjectTypeId = match ($subjectType) {
            'client' => DomainConsentSubjectType::CLIENT,
            'caregiver' => DomainConsentSubjectType::CAREGIVER,
            default => null,
        };

        if (!$subjectTypeId) {
            return $this->error('Tipo de titular invalido');
        }

        $consents = $this->consentService->getActiveConsents($subjectTypeId, $subjectId);

        return $this->success($consents);
    }

    /**
     * Verifica consentimento ativo.
     */
    public function check(string $subjectType, int $subjectId, string $consentType): JsonResponse
    {
        $subjectTypeId = match ($subjectType) {
            'client' => DomainConsentSubjectType::CLIENT,
            'caregiver' => DomainConsentSubjectType::CAREGIVER,
            default => null,
        };

        if (!$subjectTypeId) {
            return $this->error('Tipo de titular invalido');
        }

        $hasConsent = $this->consentService->hasConsent($subjectTypeId, $subjectId, $consentType);

        return $this->success([
            'has_consent' => $hasConsent,
            'consent_type' => $consentType,
        ]);
    }

    /**
     * Historico de consentimentos.
     */
    public function history(string $subjectType, int $subjectId): JsonResponse
    {
        $subjectTypeId = match ($subjectType) {
            'client' => DomainConsentSubjectType::CLIENT,
            'caregiver' => DomainConsentSubjectType::CAREGIVER,
            default => null,
        };

        if (!$subjectTypeId) {
            return $this->error('Tipo de titular invalido');
        }

        $history = $this->consentService->getHistory($subjectTypeId, $subjectId);

        return $this->success($history);
    }
}
