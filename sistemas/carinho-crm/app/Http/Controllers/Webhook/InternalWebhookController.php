<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Domain\DomainLeadStatus;
use App\Models\Domain\DomainContractStatus;
use App\Models\Domain\DomainInteractionChannel;
use App\Services\LeadService;
use App\Services\ClientService;
use App\Services\ContractService;
use App\Services\InteractionService;
use App\Events\LeadCreated;
use App\Events\ContractSigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para webhooks de sistemas internos Carinho
 */
class InternalWebhookController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected ClientService $clientService,
        protected ContractService $contractService,
        protected InteractionService $interactionService
    ) {}

    /**
     * Recebe novo lead do site
     */
    public function siteNewLead(Request $request)
    {
        Log::channel('integrations')->info('Webhook: site new lead', $request->all());

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'email' => 'nullable|email',
            'city' => 'required|string|max:128',
            'urgency_id' => 'required|integer',
            'service_type_id' => 'required|integer',
            'source' => 'nullable|string|max:128',
            'utm_id' => 'nullable|integer',
        ]);

        $data = $request->all();
        $data['source'] = $data['source'] ?? 'site';

        $lead = $this->leadService->createLead($data);

        event(new LeadCreated($lead));

        return response()->json([
            'status' => 'success',
            'lead_id' => $lead->id,
        ], 201);
    }

    /**
     * Recebe atualização de status do atendimento
     */
    public function atendimentoStatus(Request $request)
    {
        Log::channel('integrations')->info('Webhook: atendimento status', $request->all());

        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'status_id' => 'required|exists:domain_lead_status,id',
            'notes' => 'nullable|string',
        ]);

        $lead = Lead::findOrFail($request->lead_id);
        
        $this->leadService->updateLead($lead, [
            'status_id' => $request->status_id,
        ]);

        // Se houver notas, registra como interação
        if ($request->notes) {
            $this->interactionService->createInteraction([
                'lead_id' => $lead->id,
                'channel_id' => DomainInteractionChannel::PHONE,
                'summary' => "[Atendimento] {$request->notes}",
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Recebe interação registrada no atendimento
     */
    public function atendimentoInteraction(Request $request)
    {
        Log::channel('integrations')->info('Webhook: atendimento interaction', $request->all());

        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'channel_id' => 'required|exists:domain_interaction_channel,id',
            'summary' => 'required|string',
            'occurred_at' => 'nullable|date',
        ]);

        $this->interactionService->createInteraction($request->all());

        return response()->json(['status' => 'success']);
    }

    /**
     * Recebe notificação de serviço iniciado da operação
     */
    public function operacaoServiceStarted(Request $request)
    {
        Log::channel('integrations')->info('Webhook: operacao service started', $request->all());

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_date' => 'required|date',
            'caregiver_name' => 'nullable|string',
        ]);

        $client = Client::with('lead')->findOrFail($request->client_id);

        if ($client->lead) {
            $caregiverInfo = $request->caregiver_name ? " com {$request->caregiver_name}" : '';
            $this->interactionService->createInteraction([
                'lead_id' => $client->lead_id,
                'channel_id' => DomainInteractionChannel::PHONE,
                'summary' => "[Operação] Serviço iniciado{$caregiverInfo} em {$request->service_date}",
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Recebe notificação de serviço concluído da operação
     */
    public function operacaoServiceCompleted(Request $request)
    {
        Log::channel('integrations')->info('Webhook: operacao service completed', $request->all());

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $client = Client::with('lead')->findOrFail($request->client_id);

        if ($client->lead) {
            $notes = $request->notes ? ": {$request->notes}" : '';
            $this->interactionService->createInteraction([
                'lead_id' => $client->lead_id,
                'channel_id' => DomainInteractionChannel::PHONE,
                'summary' => "[Operação] Serviço concluído em {$request->service_date}{$notes}",
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Recebe atualização de pagamento do financeiro
     */
    public function financeiroPayment(Request $request)
    {
        Log::channel('integrations')->info('Webhook: financeiro payment', $request->all());

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'payment_status' => 'required|string|in:paid,overdue,pending',
            'amount' => 'required|numeric',
            'reference_date' => 'required|date',
        ]);

        $client = Client::with('lead')->findOrFail($request->client_id);

        if ($client->lead) {
            $statusText = match($request->payment_status) {
                'paid' => 'Pagamento confirmado',
                'overdue' => 'Pagamento em atraso',
                'pending' => 'Pagamento pendente',
            };

            $amount = 'R$ ' . number_format($request->amount, 2, ',', '.');

            $this->interactionService->createInteraction([
                'lead_id' => $client->lead_id,
                'channel_id' => DomainInteractionChannel::EMAIL,
                'summary' => "[Financeiro] {$statusText} - {$amount} ref. {$request->reference_date}",
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Recebe tracking de UTM do marketing
     */
    public function marketingUtm(Request $request)
    {
        Log::channel('integrations')->info('Webhook: marketing utm', $request->all());

        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'utm_id' => 'required|integer',
        ]);

        $lead = Lead::findOrFail($request->lead_id);
        $lead->utm_id = $request->utm_id;
        $lead->save();

        return response()->json(['status' => 'success']);
    }

    /**
     * Recebe confirmação de assinatura de contrato do sistema de documentos
     */
    public function documentosContractSigned(Request $request)
    {
        Log::channel('integrations')->info('Webhook: documentos contract signed', $request->all());

        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'signed_at' => 'required|date',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string',
        ]);

        $contract = Contract::findOrFail($request->contract_id);

        if ($contract->isDraft()) {
            $contract->status_id = DomainContractStatus::SIGNED;
            $contract->signed_at = $request->signed_at;
            $contract->save();

            event(new ContractSigned($contract));

            // Registra interação no lead do cliente
            if ($contract->client?->lead_id) {
                $this->interactionService->createInteraction([
                    'lead_id' => $contract->client->lead_id,
                    'channel_id' => DomainInteractionChannel::EMAIL,
                    'summary' => '[Documentos] Contrato assinado digitalmente',
                ]);
            }
        }

        return response()->json(['status' => 'success']);
    }
}
