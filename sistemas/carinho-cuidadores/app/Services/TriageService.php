<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Models\DomainDocumentType;
use Illuminate\Support\Facades\Log;

class TriageService
{
    public function __construct(
        private DocumentValidationService $documentValidationService
    ) {}

    /**
     * Verifica elegibilidade do cuidador para ativacao.
     */
    public function checkEligibility(Caregiver $caregiver): array
    {
        $checks = [
            'documents' => $this->checkDocuments($caregiver),
            'contract' => $this->checkContract($caregiver),
            'profile' => $this->checkProfile($caregiver),
            'availability' => $this->checkAvailability($caregiver),
            'regions' => $this->checkRegions($caregiver),
        ];

        $isEligible = collect($checks)->every(fn ($check) => $check['passed']);

        $result = [
            'is_eligible' => $isEligible,
            'checks' => $checks,
            'missing_requirements' => collect($checks)
                ->filter(fn ($check) => !$check['passed'])
                ->map(fn ($check) => $check['message'])
                ->values()
                ->toArray(),
        ];

        Log::info('Verificacao de elegibilidade', [
            'caregiver_id' => $caregiver->id,
            'is_eligible' => $isEligible,
        ]);

        return $result;
    }

    /**
     * Verifica documentos obrigatorios.
     */
    private function checkDocuments(Caregiver $caregiver): array
    {
        $missingDocs = $this->documentValidationService->getMissingRequiredDocuments($caregiver);
        $passed = empty($missingDocs);

        return [
            'name' => 'Documentos obrigatorios',
            'passed' => $passed,
            'message' => $passed
                ? 'Todos os documentos obrigatorios estao aprovados'
                : 'Faltam documentos: ' . implode(', ', $missingDocs),
            'details' => [
                'required' => DomainDocumentType::required(),
                'missing' => $missingDocs,
            ],
        ];
    }

    /**
     * Verifica contrato assinado.
     */
    private function checkContract(Caregiver $caregiver): array
    {
        $hasSignedContract = $caregiver->contracts()
            ->whereHas('status', fn ($q) => $q->whereIn('code', ['signed', 'active']))
            ->exists();

        return [
            'name' => 'Contrato assinado',
            'passed' => $hasSignedContract,
            'message' => $hasSignedContract
                ? 'Contrato assinado e ativo'
                : 'Cuidador precisa assinar o termo de responsabilidade',
        ];
    }

    /**
     * Verifica completude do perfil.
     */
    private function checkProfile(Caregiver $caregiver): array
    {
        $issues = [];

        if (empty($caregiver->name)) {
            $issues[] = 'Nome nao informado';
        }

        if (empty($caregiver->phone)) {
            $issues[] = 'Telefone nao informado';
        }

        if (empty($caregiver->city)) {
            $issues[] = 'Cidade nao informada';
        }

        // Verifica experiencia minima se configurado
        $minExperience = config('cuidadores.triagem.experiencia_minima_anos', 0);
        if ($minExperience > 0 && $caregiver->experience_years < $minExperience) {
            $issues[] = "Experiencia minima de {$minExperience} ano(s) requerida";
        }

        // Verifica se tem pelo menos uma habilidade
        if ($caregiver->skills()->count() === 0) {
            $issues[] = 'Nenhuma habilidade/tipo de cuidado informado';
        }

        $passed = empty($issues);

        return [
            'name' => 'Perfil completo',
            'passed' => $passed,
            'message' => $passed
                ? 'Perfil esta completo'
                : 'Perfil incompleto: ' . implode('; ', $issues),
            'details' => $issues,
        ];
    }

    /**
     * Verifica disponibilidade cadastrada.
     */
    private function checkAvailability(Caregiver $caregiver): array
    {
        $hasAvailability = $caregiver->availability()->count() > 0;

        return [
            'name' => 'Disponibilidade',
            'passed' => $hasAvailability,
            'message' => $hasAvailability
                ? 'Disponibilidade cadastrada'
                : 'Cuidador precisa informar disponibilidade de horarios',
        ];
    }

    /**
     * Verifica regioes de atuacao.
     */
    private function checkRegions(Caregiver $caregiver): array
    {
        $hasRegions = $caregiver->regions()->count() > 0;

        return [
            'name' => 'Regioes de atuacao',
            'passed' => $hasRegions,
            'message' => $hasRegions
                ? 'Regioes de atuacao cadastradas'
                : 'Cuidador precisa informar regioes onde pode atuar',
        ];
    }

    /**
     * Classifica cuidador por nivel de prontidao.
     */
    public function classifyReadiness(Caregiver $caregiver): string
    {
        $eligibility = $this->checkEligibility($caregiver);

        if ($eligibility['is_eligible']) {
            // Verifica qualidade adicional
            $rating = $caregiver->average_rating;
            $incidentCount = $caregiver->incidents()->recent(90)->count();

            if ($rating !== null && $rating >= 4.5 && $incidentCount === 0) {
                return 'premium';
            }

            if ($rating !== null && $rating >= 4.0 && $incidentCount <= 1) {
                return 'standard';
            }

            return 'basic';
        }

        $failedChecks = count($eligibility['missing_requirements']);

        if ($failedChecks <= 2) {
            return 'almost_ready';
        }

        return 'incomplete';
    }

    /**
     * Gera resumo de triagem para exibicao.
     */
    public function getTriageSummary(Caregiver $caregiver): array
    {
        $eligibility = $this->checkEligibility($caregiver);
        $readiness = $this->classifyReadiness($caregiver);

        $readinessLabels = [
            'premium' => 'Premium - Pronto para servicos prioritarios',
            'standard' => 'Padrao - Pronto para ativacao',
            'basic' => 'Basico - Pronto com restricoes',
            'almost_ready' => 'Quase pronto - Poucos ajustes necessarios',
            'incomplete' => 'Incompleto - Varios requisitos pendentes',
        ];

        return [
            'caregiver_id' => $caregiver->id,
            'name' => $caregiver->name,
            'current_status' => $caregiver->status?->code,
            'is_eligible_for_activation' => $eligibility['is_eligible'],
            'readiness_level' => $readiness,
            'readiness_label' => $readinessLabels[$readiness] ?? $readiness,
            'checks' => collect($eligibility['checks'])->map(fn ($check) => [
                'name' => $check['name'],
                'passed' => $check['passed'],
                'message' => $check['message'],
            ])->toArray(),
            'next_steps' => $eligibility['missing_requirements'],
        ];
    }
}
