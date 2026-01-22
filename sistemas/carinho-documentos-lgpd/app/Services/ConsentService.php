<?php

namespace App\Services;

use App\Models\Consent;
use App\Models\DomainConsentSubjectType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciamento de consentimentos LGPD.
 */
class ConsentService
{
    private const CACHE_PREFIX = 'consent:';
    private const CACHE_TTL = 3600; // 1 hora

    /**
     * Registra consentimento.
     */
    public function grant(
        int $subjectTypeId,
        int $subjectId,
        string $consentType,
        string $source
    ): ?Consent {
        try {
            // Verifica se ja existe consentimento ativo
            if (Consent::hasActiveConsent($subjectTypeId, $subjectId, $consentType)) {
                Log::info('Consent already active', [
                    'subject_type' => $subjectTypeId,
                    'subject_id' => $subjectId,
                    'consent_type' => $consentType,
                ]);

                return Consent::where('subject_type_id', $subjectTypeId)
                    ->where('subject_id', $subjectId)
                    ->where('consent_type', $consentType)
                    ->whereNull('revoked_at')
                    ->first();
            }

            $consent = Consent::create([
                'subject_type_id' => $subjectTypeId,
                'subject_id' => $subjectId,
                'consent_type' => $consentType,
                'granted_at' => now(),
                'source' => $source,
            ]);

            // Limpa cache
            $this->clearCache($subjectTypeId, $subjectId);

            Log::info('Consent granted', [
                'consent_id' => $consent->id,
                'subject_type' => $subjectTypeId,
                'subject_id' => $subjectId,
                'consent_type' => $consentType,
                'source' => $source,
            ]);

            return $consent;
        } catch (\Throwable $e) {
            Log::error('Failed to grant consent', [
                'error' => $e->getMessage(),
                'subject_type' => $subjectTypeId,
                'subject_id' => $subjectId,
            ]);

            return null;
        }
    }

    /**
     * Registra consentimento de cliente.
     */
    public function grantForClient(int $clientId, string $consentType, string $source): ?Consent
    {
        return $this->grant(
            DomainConsentSubjectType::CLIENT,
            $clientId,
            $consentType,
            $source
        );
    }

    /**
     * Registra consentimento de cuidador.
     */
    public function grantForCaregiver(int $caregiverId, string $consentType, string $source): ?Consent
    {
        return $this->grant(
            DomainConsentSubjectType::CAREGIVER,
            $caregiverId,
            $consentType,
            $source
        );
    }

    /**
     * Registra multiplos consentimentos.
     */
    public function grantMultiple(
        int $subjectTypeId,
        int $subjectId,
        array $consentTypes,
        string $source
    ): array {
        $consents = [];

        foreach ($consentTypes as $type) {
            $consent = $this->grant($subjectTypeId, $subjectId, $type, $source);
            if ($consent) {
                $consents[] = $consent;
            }
        }

        return $consents;
    }

    /**
     * Revoga consentimento.
     */
    public function revoke(int $consentId): bool
    {
        try {
            $consent = Consent::findOrFail($consentId);

            if (!$consent->isActive()) {
                return true; // Ja revogado
            }

            $consent->revoke();

            // Limpa cache
            $this->clearCache($consent->subject_type_id, $consent->subject_id);

            Log::info('Consent revoked', [
                'consent_id' => $consentId,
                'subject_type' => $consent->subject_type_id,
                'subject_id' => $consent->subject_id,
                'consent_type' => $consent->consent_type,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to revoke consent', [
                'consent_id' => $consentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Revoga consentimento por tipo.
     */
    public function revokeByType(
        int $subjectTypeId,
        int $subjectId,
        string $consentType
    ): bool {
        try {
            $consent = Consent::where('subject_type_id', $subjectTypeId)
                ->where('subject_id', $subjectId)
                ->where('consent_type', $consentType)
                ->whereNull('revoked_at')
                ->first();

            if (!$consent) {
                return true; // Nao existe consentimento ativo
            }

            return $this->revoke($consent->id);
        } catch (\Throwable $e) {
            Log::error('Failed to revoke consent by type', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Revoga todos os consentimentos de um titular.
     */
    public function revokeAll(int $subjectTypeId, int $subjectId): int
    {
        try {
            $count = Consent::where('subject_type_id', $subjectTypeId)
                ->where('subject_id', $subjectId)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            // Limpa cache
            $this->clearCache($subjectTypeId, $subjectId);

            Log::info('All consents revoked', [
                'subject_type' => $subjectTypeId,
                'subject_id' => $subjectId,
                'count' => $count,
            ]);

            return $count;
        } catch (\Throwable $e) {
            Log::error('Failed to revoke all consents', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Verifica se tem consentimento ativo.
     */
    public function hasConsent(
        int $subjectTypeId,
        int $subjectId,
        string $consentType
    ): bool {
        $cacheKey = $this->getCacheKey($subjectTypeId, $subjectId, $consentType);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($subjectTypeId, $subjectId, $consentType) {
            return Consent::hasActiveConsent($subjectTypeId, $subjectId, $consentType);
        });
    }

    /**
     * Obtem todos os consentimentos ativos.
     */
    public function getActiveConsents(int $subjectTypeId, int $subjectId): array
    {
        $cacheKey = $this->getCacheKey($subjectTypeId, $subjectId, 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($subjectTypeId, $subjectId) {
            return Consent::getActiveForSubject($subjectTypeId, $subjectId)
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'consent_type' => $c->consent_type,
                    'granted_at' => $c->granted_at->toIso8601String(),
                    'source' => $c->source,
                ])
                ->toArray();
        });
    }

    /**
     * Obtem historico completo de consentimentos.
     */
    public function getHistory(int $subjectTypeId, int $subjectId): array
    {
        return Consent::getHistoryForSubject($subjectTypeId, $subjectId)
            ->map(fn ($c) => [
                'id' => $c->id,
                'consent_type' => $c->consent_type,
                'granted_at' => $c->granted_at->toIso8601String(),
                'source' => $c->source,
                'revoked_at' => $c->revoked_at?->toIso8601String(),
                'is_active' => $c->isActive(),
            ])
            ->toArray();
    }

    /**
     * Obtem resumo de consentimentos para exibicao.
     */
    public function getSummary(int $subjectTypeId, int $subjectId): array
    {
        $consents = $this->getActiveConsents($subjectTypeId, $subjectId);

        return [
            'subject_type' => DomainConsentSubjectType::CODES[$subjectTypeId] ?? 'unknown',
            'subject_id' => $subjectId,
            'active_consents' => array_column($consents, 'consent_type'),
            'total_active' => count($consents),
            'consent_details' => $consents,
        ];
    }

    /**
     * Gera chave de cache.
     */
    private function getCacheKey(int $subjectTypeId, int $subjectId, string $type): string
    {
        return self::CACHE_PREFIX . "{$subjectTypeId}:{$subjectId}:{$type}";
    }

    /**
     * Limpa cache do titular.
     */
    private function clearCache(int $subjectTypeId, int $subjectId): void
    {
        foreach (array_keys(Consent::TYPES) as $type) {
            Cache::forget($this->getCacheKey($subjectTypeId, $subjectId, $type));
        }
        Cache::forget($this->getCacheKey($subjectTypeId, $subjectId, 'all'));
    }
}
