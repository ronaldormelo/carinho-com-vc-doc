<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Models\CaregiverContract;
use App\Models\DomainContractStatus;
use App\Integrations\Documentos\DocumentosClient;
use App\Integrations\WhatsApp\ZApiClient;
use App\Jobs\ProcessContractSign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContractService
{
    public function __construct(
        private DocumentosClient $documentosClient,
        private ZApiClient $zApiClient
    ) {}

    /**
     * Cria novo contrato para o cuidador.
     */
    public function createContract(Caregiver $caregiver, string $contractType): array
    {
        // Verifica se ja tem contrato ativo do mesmo tipo
        $existingActive = $caregiver->contracts()
            ->active()
            ->exists();

        if ($existingActive && $contractType === 'termo_responsabilidade') {
            return [
                'success' => false,
                'message' => 'Cuidador ja possui termo de responsabilidade ativo',
            ];
        }

        // Cria contrato no sistema de documentos
        $result = $this->documentosClient->createContract([
            'type' => $contractType,
            'caregiver_id' => $caregiver->id,
            'caregiver_name' => $caregiver->name,
            'caregiver_phone' => $caregiver->phone,
            'caregiver_email' => $caregiver->email,
            'template' => $this->getContractTemplate($contractType),
        ]);

        if (!$result['ok']) {
            Log::error('Falha ao criar contrato no sistema de documentos', [
                'caregiver_id' => $caregiver->id,
                'contract_type' => $contractType,
                'response' => $result,
            ]);

            return [
                'success' => false,
                'message' => 'Falha ao gerar contrato',
            ];
        }

        $externalContractId = $result['body']['contract_id'] ?? 0;

        $contract = CaregiverContract::create([
            'caregiver_id' => $caregiver->id,
            'contract_id' => $externalContractId,
            'status_id' => DomainContractStatus::DRAFT,
        ]);

        Log::info('Contrato criado', [
            'contract_id' => $contract->id,
            'caregiver_id' => $caregiver->id,
            'type' => $contractType,
        ]);

        return [
            'success' => true,
            'contract' => $contract->load('status'),
        ];
    }

    /**
     * Registra assinatura do contrato.
     */
    public function signContract(CaregiverContract $contract, array $signatureData): array
    {
        // Envia assinatura para o sistema de documentos
        $result = $this->documentosClient->signContract($contract->contract_id, [
            'signature' => $signatureData['signature'] ?? null,
            'ip_address' => $signatureData['ip_address'] ?? null,
            'user_agent' => $signatureData['user_agent'] ?? null,
            'signed_at' => now()->toIso8601String(),
        ]);

        if (!$result['ok']) {
            Log::error('Falha ao registrar assinatura', [
                'contract_id' => $contract->id,
                'response' => $result,
            ]);

            return [
                'success' => false,
                'message' => 'Falha ao registrar assinatura',
            ];
        }

        $contract->update([
            'status_id' => DomainContractStatus::SIGNED,
            'signed_at' => now(),
        ]);

        // Dispara processamento assincrono
        ProcessContractSign::dispatch($contract);

        Log::info('Contrato assinado', [
            'contract_id' => $contract->id,
            'caregiver_id' => $contract->caregiver_id,
        ]);

        return [
            'success' => true,
            'message' => 'Contrato assinado com sucesso',
        ];
    }

    /**
     * Envia contrato por WhatsApp ou email.
     */
    public function sendContract(CaregiverContract $contract, string $channel): array
    {
        $caregiver = $contract->caregiver;

        // Gera link para assinatura
        $signUrl = $this->generateSignatureUrl($contract);

        if ($channel === 'whatsapp') {
            return $this->sendViaWhatsApp($caregiver, $contract, $signUrl);
        }

        if ($channel === 'email') {
            return $this->sendViaEmail($caregiver, $contract, $signUrl);
        }

        return [
            'success' => false,
            'message' => 'Canal de envio invalido',
        ];
    }

    /**
     * Envia contrato via WhatsApp.
     */
    private function sendViaWhatsApp(Caregiver $caregiver, CaregiverContract $contract, string $signUrl): array
    {
        $message = $this->buildContractMessage($caregiver, $signUrl);

        $result = $this->zApiClient->sendTextMessage($caregiver->phone, $message);

        if (!$result['ok']) {
            Log::error('Falha ao enviar contrato via WhatsApp', [
                'caregiver_id' => $caregiver->id,
                'contract_id' => $contract->id,
                'response' => $result,
            ]);

            return [
                'success' => false,
                'message' => 'Falha ao enviar WhatsApp',
            ];
        }

        Log::info('Contrato enviado via WhatsApp', [
            'contract_id' => $contract->id,
            'phone' => $caregiver->phone,
        ]);

        return [
            'success' => true,
            'message' => 'Contrato enviado via WhatsApp',
        ];
    }

    /**
     * Envia contrato via email.
     */
    private function sendViaEmail(Caregiver $caregiver, CaregiverContract $contract, string $signUrl): array
    {
        if (empty($caregiver->email)) {
            return [
                'success' => false,
                'message' => 'Cuidador nao possui email cadastrado',
            ];
        }

        try {
            Mail::send('emails.contrato_digital', [
                'caregiver' => $caregiver,
                'signUrl' => $signUrl,
                'brandName' => config('branding.name'),
            ], function ($mail) use ($caregiver) {
                $mail->to($caregiver->email, $caregiver->name)
                    ->subject('Seu Contrato - Carinho com Voce')
                    ->from(
                        config('integrations.email.from'),
                        config('branding.email.signature_name')
                    );
            });

            Log::info('Contrato enviado via email', [
                'contract_id' => $contract->id,
                'email' => $caregiver->email,
            ]);

            return [
                'success' => true,
                'message' => 'Contrato enviado via email',
            ];
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar contrato via email', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Falha ao enviar email',
            ];
        }
    }

    /**
     * Gera URL para assinatura do contrato.
     */
    private function generateSignatureUrl(CaregiverContract $contract): string
    {
        $result = $this->documentosClient->getSignatureUrl($contract->contract_id);

        if ($result['ok'] && !empty($result['body']['url'])) {
            return $result['body']['url'];
        }

        // Fallback URL
        $baseUrl = config('branding.subdomain', 'cuidadores.carinho.com.vc');
        return "https://{$baseUrl}/contratos/{$contract->id}/assinar";
    }

    /**
     * Monta mensagem de envio do contrato.
     */
    private function buildContractMessage(Caregiver $caregiver, string $signUrl): string
    {
        $brandName = config('branding.name', 'Carinho com Voce');

        return <<<MSG
Ola, {$caregiver->name}!

Seu termo de responsabilidade da {$brandName} esta pronto para assinatura.

Acesse o link abaixo para revisar e assinar:
{$signUrl}

Em caso de duvidas, estamos a disposicao.

Atenciosamente,
Equipe {$brandName}
MSG;
    }

    /**
     * Retorna template do contrato.
     */
    private function getContractTemplate(string $contractType): string
    {
        return match ($contractType) {
            'termo_responsabilidade' => 'termo_responsabilidade_cuidador_v1',
            'contrato_prestacao' => 'contrato_prestacao_servicos_v1',
            default => 'termo_responsabilidade_cuidador_v1',
        };
    }
}
