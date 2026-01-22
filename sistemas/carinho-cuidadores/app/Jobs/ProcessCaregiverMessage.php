<?php

namespace App\Jobs;

use App\Models\Caregiver;
use App\Integrations\WhatsApp\ZApiClient;
use App\Integrations\Atendimento\AtendimentoClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCaregiverMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(
        private array $messageData
    ) {
        $this->onQueue('messages');
    }

    public function handle(ZApiClient $zApiClient, AtendimentoClient $atendimentoClient): void
    {
        $phone = $this->messageData['phone'] ?? '';
        $body = $this->messageData['body'] ?? '';
        $name = $this->messageData['name'] ?? '';

        Log::info('Processando mensagem de cuidador', [
            'phone' => $phone,
        ]);

        // Busca cuidador pelo telefone
        $caregiver = Caregiver::where('phone', $phone)->first();

        if (!$caregiver) {
            // Cuidador nao encontrado - encaminha para atendimento
            $this->forwardToSupport($atendimentoClient, $phone, $body, $name);
            return;
        }

        // Processa mensagem baseado no contexto
        $this->processMessage($caregiver, $body, $zApiClient, $atendimentoClient);
    }

    private function processMessage(
        Caregiver $caregiver,
        string $body,
        ZApiClient $zApiClient,
        AtendimentoClient $atendimentoClient
    ): void {
        $lowerBody = mb_strtolower(trim($body));

        // Comandos simples
        if (in_array($lowerBody, ['status', 'meu status', 'situacao'])) {
            $this->sendStatusResponse($caregiver, $zApiClient);
            return;
        }

        if (in_array($lowerBody, ['ajuda', 'help', 'menu'])) {
            $this->sendHelpResponse($caregiver, $zApiClient);
            return;
        }

        if (in_array($lowerBody, ['disponibilidade', 'horarios'])) {
            $this->sendAvailabilityResponse($caregiver, $zApiClient);
            return;
        }

        // Mensagem nao reconhecida - encaminha para atendimento humano
        $atendimentoClient->sendMessage($caregiver->phone, $body, [
            'caregiver_id' => $caregiver->id,
            'caregiver_name' => $caregiver->name,
            'forward_to_support' => true,
        ]);

        Log::info('Mensagem encaminhada para atendimento', [
            'caregiver_id' => $caregiver->id,
        ]);
    }

    private function sendStatusResponse(Caregiver $caregiver, ZApiClient $zApiClient): void
    {
        $status = $caregiver->status?->label ?? 'Desconhecido';
        $rating = $caregiver->average_rating;
        $ratingText = $rating ? number_format($rating, 1) . '/5' : 'Sem avaliacoes';

        $message = <<<MSG
Ola, {$caregiver->name}!

Seu status atual: {$status}

Avaliacao media: {$ratingText}

Para mais informacoes, digite "ajuda".
MSG;

        $zApiClient->sendTextMessage($caregiver->phone, $message);
    }

    private function sendHelpResponse(Caregiver $caregiver, ZApiClient $zApiClient): void
    {
        $brandName = config('branding.name', 'Carinho com Voce');

        $message = <<<MSG
Ola, {$caregiver->name}!

Comandos disponiveis:

- "status" - Ver seu status atual
- "disponibilidade" - Ver seus horarios
- "ajuda" - Ver este menu

Para outras questoes, envie sua mensagem que nossa equipe respondera em breve.

{$brandName}
MSG;

        $zApiClient->sendTextMessage($caregiver->phone, $message);
    }

    private function sendAvailabilityResponse(Caregiver $caregiver, ZApiClient $zApiClient): void
    {
        $availability = $caregiver->availability()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        if ($availability->isEmpty()) {
            $message = "Voce ainda nao cadastrou sua disponibilidade de horarios.";
        } else {
            $lines = $availability->map(fn ($a) => $a->display)->implode("\n");
            $message = "Sua disponibilidade cadastrada:\n\n{$lines}";
        }

        $zApiClient->sendTextMessage($caregiver->phone, $message);
    }

    private function forwardToSupport(
        AtendimentoClient $atendimentoClient,
        string $phone,
        string $body,
        string $name
    ): void {
        // Cria conversa no sistema de atendimento
        $atendimentoClient->createConversation([
            'phone' => $phone,
            'name' => $name,
            'source' => 'carinho-cuidadores',
            'type' => 'new_caregiver',
        ]);

        Log::info('Mensagem de numero desconhecido encaminhada para atendimento', [
            'phone' => $phone,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job de processamento de mensagem falhou', [
            'phone' => $this->messageData['phone'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'message-processing',
            'phone:' . ($this->messageData['phone'] ?? 'unknown'),
        ];
    }
}
