<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Integrations\WhatsApp\ZApiClient;
use App\Integrations\Atendimento\AtendimentoClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(
        private ZApiClient $zApiClient,
        private AtendimentoClient $atendimentoClient
    ) {}

    /**
     * Envia notificacao para o cuidador.
     */
    public function send(Caregiver $caregiver, string $type, array $data = []): array
    {
        $message = $this->buildMessage($type, $caregiver, $data);

        if (empty($message)) {
            return ['success' => false, 'message' => 'Tipo de notificacao desconhecido'];
        }

        $results = [];

        // Envia via WhatsApp (canal principal)
        if (!empty($caregiver->phone)) {
            $whatsappResult = $this->sendWhatsApp($caregiver->phone, $message);
            $results['whatsapp'] = $whatsappResult;
        }

        // Envia via email se disponivel e se for um tipo importante
        if (!empty($caregiver->email) && $this->shouldSendEmail($type)) {
            $emailResult = $this->sendEmail($caregiver, $type, $message, $data);
            $results['email'] = $emailResult;
        }

        // Registra no sistema de atendimento
        $this->atendimentoClient->logNotification([
            'caregiver_id' => $caregiver->id,
            'type' => $type,
            'channels' => array_keys($results),
            'timestamp' => now()->toIso8601String(),
        ]);

        Log::info('Notificacao enviada', [
            'caregiver_id' => $caregiver->id,
            'type' => $type,
            'results' => $results,
        ]);

        return [
            'success' => collect($results)->contains(fn ($r) => $r['success'] ?? false),
            'results' => $results,
        ];
    }

    /**
     * Envia mensagem via WhatsApp.
     */
    public function sendWhatsApp(string $phone, string $message): array
    {
        $result = $this->zApiClient->sendTextMessage($phone, $message);

        return [
            'success' => $result['ok'] ?? false,
            'channel' => 'whatsapp',
        ];
    }

    /**
     * Envia mensagem via email.
     */
    public function sendEmail(Caregiver $caregiver, string $type, string $message, array $data): array
    {
        try {
            $subject = $this->getEmailSubject($type);

            Mail::send('emails.notificacao', [
                'caregiver' => $caregiver,
                'message' => $message,
                'type' => $type,
                'data' => $data,
                'brandName' => config('branding.name'),
            ], function ($mail) use ($caregiver, $subject) {
                $mail->to($caregiver->email, $caregiver->name)
                    ->subject($subject)
                    ->from(
                        config('integrations.email.from'),
                        config('branding.email.signature_name')
                    );
            });

            return ['success' => true, 'channel' => 'email'];
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar email', [
                'caregiver_id' => $caregiver->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'channel' => 'email', 'error' => $e->getMessage()];
        }
    }

    /**
     * Constroi mensagem baseada no tipo.
     */
    private function buildMessage(string $type, Caregiver $caregiver, array $data): ?string
    {
        $brandName = config('branding.name', 'Carinho com Voce');
        $messages = config('branding.messages', []);

        return match ($type) {
            'welcome' => $this->buildWelcomeMessage($caregiver, $brandName),
            'activated' => $this->buildActivatedMessage($caregiver, $brandName),
            'deactivated' => $this->buildDeactivatedMessage($caregiver, $brandName, $data),
            'blocked' => $this->buildBlockedMessage($caregiver, $brandName, $data),
            'document_pending' => $this->buildDocumentPendingMessage($caregiver, $brandName),
            'document_approved' => $this->buildDocumentApprovedMessage($caregiver, $brandName, $data),
            'document_rejected' => $this->buildDocumentRejectedMessage($caregiver, $brandName, $data),
            'contract_ready' => $this->buildContractReadyMessage($caregiver, $brandName, $data),
            'rating_received' => $this->buildRatingReceivedMessage($caregiver, $brandName, $data),
            default => null,
        };
    }

    private function buildWelcomeMessage(Caregiver $caregiver, string $brandName): string
    {
        return <<<MSG
Ola, {$caregiver->name}!

Seja bem-vindo(a) a {$brandName}! Estamos felizes em te-lo(a) como parte da nossa equipe de cuidadores.

Proximos passos:
1. Complete seu cadastro com todas as informacoes
2. Envie os documentos obrigatorios
3. Assine o termo de responsabilidade

Assim que tudo estiver pronto, voce podera receber oportunidades de servico.

Qualquer duvida, estamos aqui para ajudar!

Atenciosamente,
Equipe {$brandName}
MSG;
    }

    private function buildActivatedMessage(Caregiver $caregiver, string $brandName): string
    {
        return <<<MSG
Parabens, {$caregiver->name}!

Seu cadastro na {$brandName} foi aprovado e ativado com sucesso!

A partir de agora voce esta apto(a) a receber oportunidades de servico compativeis com seu perfil e disponibilidade.

Mantenha seus dados sempre atualizados para receber as melhores oportunidades.

Boas vindas a equipe!

Atenciosamente,
Equipe {$brandName}
MSG;
    }

    private function buildDeactivatedMessage(Caregiver $caregiver, string $brandName, array $data): string
    {
        $reason = $data['reason'] ?? 'Motivo nao informado';

        return <<<MSG
Ola, {$caregiver->name}.

Informamos que seu cadastro na {$brandName} foi temporariamente desativado.

Motivo: {$reason}

Se desejar reativar seu cadastro ou tiver duvidas, entre em contato conosco.

Atenciosamente,
Equipe {$brandName}
MSG;
    }

    private function buildBlockedMessage(Caregiver $caregiver, string $brandName, array $data): string
    {
        $reason = $data['reason'] ?? 'Motivo nao informado';

        return <<<MSG
Ola, {$caregiver->name}.

Informamos que seu cadastro na {$brandName} foi bloqueado.

Motivo: {$reason}

Para mais informacoes, entre em contato com nossa equipe.

Atenciosamente,
Equipe {$brandName}
MSG;
    }

    private function buildDocumentPendingMessage(Caregiver $caregiver, string $brandName): string
    {
        return <<<MSG
Ola, {$caregiver->name}!

Recebemos seu documento e ele esta em analise.

Em breve voce recebera uma atualizacao sobre o status da validacao.

Atenciosamente,
Equipe {$brandName}
MSG;
    }

    private function buildDocumentApprovedMessage(Caregiver $caregiver, string $brandName, array $data): string
    {
        $docType = $data['doc_type'] ?? 'Documento';

        return <<<MSG
Ola, {$caregiver->name}!

Otima noticia! Seu documento ({$docType}) foi aprovado com sucesso.

Continue completando seu cadastro para ativar seu perfil.

Atenciosamente,
Equipe {$brandName}
MSG;
    }

    private function buildDocumentRejectedMessage(Caregiver $caregiver, string $brandName, array $data): string
    {
        $docType = $data['doc_type'] ?? 'Documento';
        $reason = $data['reason'] ?? 'Documento nao atende aos criterios minimos';

        return <<<MSG
Ola, {$caregiver->name}.

Infelizmente seu documento ({$docType}) foi recusado.

Motivo: {$reason}

Por favor, envie novamente um documento valido e legivel.

Atenciosamente,
Equipe {$brandName}
MSG;
    }

    private function buildContractReadyMessage(Caregiver $caregiver, string $brandName, array $data): string
    {
        $signUrl = $data['sign_url'] ?? '';

        return <<<MSG
Ola, {$caregiver->name}!

Seu termo de responsabilidade esta pronto para assinatura.

Acesse o link abaixo para revisar e assinar:
{$signUrl}

Apos a assinatura, seu cadastro podera ser ativado.

Atenciosamente,
Equipe {$brandName}
MSG;
    }

    private function buildRatingReceivedMessage(Caregiver $caregiver, string $brandName, array $data): string
    {
        $score = $data['score'] ?? 'N/A';
        $comment = $data['comment'] ?? '';

        $commentText = $comment ? "\nComentario: \"{$comment}\"" : '';

        return <<<MSG
Ola, {$caregiver->name}!

Voce recebeu uma nova avaliacao!

Nota: {$score}/5{$commentText}

Continue com o otimo trabalho!

Atenciosamente,
Equipe {$brandName}
MSG;
    }

    /**
     * Determina se deve enviar email para o tipo de notificacao.
     */
    private function shouldSendEmail(string $type): bool
    {
        $emailTypes = [
            'welcome',
            'activated',
            'deactivated',
            'blocked',
            'document_approved',
            'document_rejected',
            'contract_ready',
        ];

        return in_array($type, $emailTypes);
    }

    /**
     * Retorna assunto do email baseado no tipo.
     */
    private function getEmailSubject(string $type): string
    {
        $brandName = config('branding.name', 'Carinho com Voce');

        return match ($type) {
            'welcome' => "Bem-vindo(a) a {$brandName}!",
            'activated' => "Cadastro Ativado - {$brandName}",
            'deactivated' => "Cadastro Desativado - {$brandName}",
            'blocked' => "Informacao Importante - {$brandName}",
            'document_approved' => "Documento Aprovado - {$brandName}",
            'document_rejected' => "Documento Recusado - {$brandName}",
            'contract_ready' => "Seu Contrato - {$brandName}",
            'rating_received' => "Nova Avaliacao - {$brandName}",
            default => "Notificacao - {$brandName}",
        };
    }
}
