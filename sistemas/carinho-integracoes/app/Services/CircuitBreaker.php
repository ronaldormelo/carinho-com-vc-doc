<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Implementação de Circuit Breaker para proteção de integrações.
 *
 * Estados:
 * - CLOSED: Funcionando normalmente
 * - OPEN: Circuito aberto, requisições são bloqueadas
 * - HALF_OPEN: Tentando recuperar, permite requisições limitadas
 *
 * Práticas consolidadas:
 * - Fail-fast quando sistema está indisponível
 * - Auto-recuperação após período de cooldown
 * - Logging detalhado para diagnóstico
 */
class CircuitBreaker
{
    // Estados do circuito
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    // Configurações padrão
    private int $failureThreshold;
    private int $successThreshold;
    private int $timeout;

    public function __construct(
        int $failureThreshold = 5,
        int $successThreshold = 2,
        int $timeout = 60
    ) {
        $this->failureThreshold = $failureThreshold;
        $this->successThreshold = $successThreshold;
        $this->timeout = $timeout;
    }

    /**
     * Verifica se o circuito está aberto para um serviço.
     */
    public function isOpen(string $service): bool
    {
        $state = $this->getState($service);

        if ($state === self::STATE_OPEN) {
            // Verifica se já passou o timeout para tentar novamente
            $openedAt = Cache::get("circuit_breaker:{$service}:opened_at");

            if ($openedAt && (time() - $openedAt) >= $this->timeout) {
                $this->setState($service, self::STATE_HALF_OPEN);
                Log::info("Circuit breaker half-open for {$service}", [
                    'service' => $service,
                    'timeout_seconds' => $this->timeout,
                ]);
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Verifica se pode executar requisição.
     */
    public function canExecute(string $service): bool
    {
        return !$this->isOpen($service);
    }

    /**
     * Registra sucesso de uma requisição.
     */
    public function recordSuccess(string $service): void
    {
        $state = $this->getState($service);

        if ($state === self::STATE_HALF_OPEN) {
            $successCount = $this->incrementSuccessCount($service);

            if ($successCount >= $this->successThreshold) {
                $this->close($service);
                Log::info("Circuit breaker closed for {$service}", [
                    'service' => $service,
                    'success_count' => $successCount,
                ]);
            }
        } else {
            // Reset failure count em estado normal
            $this->resetFailureCount($service);
        }
    }

    /**
     * Registra falha de uma requisição.
     */
    public function recordFailure(string $service): void
    {
        $state = $this->getState($service);

        if ($state === self::STATE_HALF_OPEN) {
            // Qualquer falha em half-open abre o circuito novamente
            $this->open($service);
            Log::warning("Circuit breaker reopened for {$service}", [
                'service' => $service,
                'reason' => 'Failure in half-open state',
            ]);
        } else {
            $failureCount = $this->incrementFailureCount($service);

            if ($failureCount >= $this->failureThreshold) {
                $this->open($service);
                Log::warning("Circuit breaker opened for {$service}", [
                    'service' => $service,
                    'failure_count' => $failureCount,
                    'threshold' => $this->failureThreshold,
                ]);
            }
        }
    }

    /**
     * Abre o circuito (bloqueia requisições).
     */
    public function open(string $service): void
    {
        $this->setState($service, self::STATE_OPEN);
        Cache::put("circuit_breaker:{$service}:opened_at", time(), 3600);
        $this->resetFailureCount($service);
        $this->resetSuccessCount($service);
    }

    /**
     * Fecha o circuito (permite requisições).
     */
    public function close(string $service): void
    {
        $this->setState($service, self::STATE_CLOSED);
        Cache::forget("circuit_breaker:{$service}:opened_at");
        $this->resetFailureCount($service);
        $this->resetSuccessCount($service);
    }

    /**
     * Reseta o circuito manualmente.
     */
    public function reset(string $service): void
    {
        $this->close($service);
        Log::info("Circuit breaker manually reset for {$service}");
    }

    /**
     * Obtém estado atual do circuito.
     */
    public function getState(string $service): string
    {
        return Cache::get("circuit_breaker:{$service}:state", self::STATE_CLOSED);
    }

    /**
     * Define estado do circuito.
     */
    private function setState(string $service, string $state): void
    {
        Cache::put("circuit_breaker:{$service}:state", $state, 3600);
    }

    /**
     * Obtém contagem de falhas.
     */
    public function getFailureCount(string $service): int
    {
        return (int) Cache::get("circuit_breaker:{$service}:failures", 0);
    }

    /**
     * Incrementa contagem de falhas.
     */
    private function incrementFailureCount(string $service): int
    {
        $key = "circuit_breaker:{$service}:failures";
        $count = Cache::increment($key);

        // Define TTL se ainda não existir
        if ($count === 1) {
            Cache::put($key, 1, 300); // 5 minutos
        }

        return $count;
    }

    /**
     * Reseta contagem de falhas.
     */
    private function resetFailureCount(string $service): void
    {
        Cache::forget("circuit_breaker:{$service}:failures");
    }

    /**
     * Incrementa contagem de sucessos (em half-open).
     */
    private function incrementSuccessCount(string $service): int
    {
        $key = "circuit_breaker:{$service}:successes";
        return Cache::increment($key);
    }

    /**
     * Reseta contagem de sucessos.
     */
    private function resetSuccessCount(string $service): void
    {
        Cache::forget("circuit_breaker:{$service}:successes");
    }

    /**
     * Obtém status completo para monitoramento.
     */
    public function getStatus(string $service): array
    {
        return [
            'service' => $service,
            'state' => $this->getState($service),
            'failure_count' => $this->getFailureCount($service),
            'failure_threshold' => $this->failureThreshold,
            'timeout_seconds' => $this->timeout,
            'opened_at' => Cache::get("circuit_breaker:{$service}:opened_at"),
        ];
    }

    /**
     * Obtém status de todos os serviços monitorados.
     */
    public function getAllStatus(): array
    {
        $services = [
            'crm',
            'operacao',
            'financeiro',
            'cuidadores',
            'atendimento',
            'marketing',
            'site',
            'documentos',
            'whatsapp',
        ];

        $status = [];
        foreach ($services as $service) {
            $status[$service] = $this->getStatus($service);
        }

        return $status;
    }
}
