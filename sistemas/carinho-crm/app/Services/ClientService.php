<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CareNeed;
use App\Models\Consent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientService
{
    /**
     * Cria um novo cliente
     */
    public function createClient(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            $client = Client::create($data);

            Log::channel('audit')->info('Cliente criado', [
                'client_id' => $client->id,
                'lead_id' => $data['lead_id'],
            ]);

            return $client;
        });
    }

    /**
     * Atualiza um cliente existente
     */
    public function updateClient(Client $client, array $data): Client
    {
        return DB::transaction(function () use ($client, $data) {
            $client->update($data);

            Log::channel('audit')->info('Cliente atualizado', [
                'client_id' => $client->id,
                'changes' => $client->getChanges(),
            ]);

            return $client->fresh();
        });
    }

    /**
     * Adiciona necessidade de cuidado ao cliente
     */
    public function addCareNeed(Client $client, array $data): CareNeed
    {
        return DB::transaction(function () use ($client, $data) {
            $careNeed = $client->careNeeds()->create($data);

            Log::channel('audit')->info('Necessidade de cuidado adicionada', [
                'client_id' => $client->id,
                'care_need_id' => $careNeed->id,
                'patient_type_id' => $data['patient_type_id'],
            ]);

            return $careNeed;
        });
    }

    /**
     * Adiciona consentimento LGPD
     */
    public function addConsent(Client $client, array $data): Consent
    {
        return DB::transaction(function () use ($client, $data) {
            $consent = $client->consents()->updateOrCreate(
                [
                    'consent_type' => $data['consent_type'],
                ],
                [
                    'granted_at' => now(),
                    'source' => $data['source'],
                ]
            );

            Log::channel('audit')->info('Consentimento registrado', [
                'client_id' => $client->id,
                'consent_type' => $data['consent_type'],
                'source' => $data['source'],
            ]);

            return $consent;
        });
    }

    /**
     * Revoga consentimento
     */
    public function revokeConsent(Client $client, string $consentType): bool
    {
        $deleted = $client->consents()
            ->where('consent_type', $consentType)
            ->delete();

        if ($deleted) {
            Log::channel('audit')->info('Consentimento revogado', [
                'client_id' => $client->id,
                'consent_type' => $consentType,
            ]);
        }

        return $deleted > 0;
    }

    /**
     * Obtém histórico completo do cliente
     */
    public function getClientHistory(Client $client): array
    {
        $client->load([
            'lead.interactions.channel',
            'lead.deals.stage',
            'lead.deals.status',
            'lead.deals.proposals.serviceType',
            'contracts.status',
            'consents',
        ]);

        $timeline = collect();

        // Adiciona criação do lead
        if ($client->lead) {
            $timeline->push([
                'type' => 'lead_created',
                'date' => $client->lead->created_at,
                'description' => 'Lead criado',
                'data' => [
                    'source' => $client->lead->source,
                ],
            ]);

            // Adiciona interações
            foreach ($client->lead->interactions as $interaction) {
                $timeline->push([
                    'type' => 'interaction',
                    'date' => $interaction->occurred_at,
                    'description' => 'Interação via ' . ($interaction->channel->label ?? 'desconhecido'),
                    'data' => [
                        'channel' => $interaction->channel->code ?? null,
                        'summary' => $interaction->summary,
                    ],
                ]);
            }

            // Adiciona deals
            foreach ($client->lead->deals as $deal) {
                $timeline->push([
                    'type' => 'deal_created',
                    'date' => $deal->created_at,
                    'description' => 'Negócio criado',
                    'data' => [
                        'stage' => $deal->stage->name ?? null,
                        'value' => $deal->value_estimated,
                    ],
                ]);

                // Adiciona propostas
                foreach ($deal->proposals as $proposal) {
                    $timeline->push([
                        'type' => 'proposal_created',
                        'date' => $proposal->created_at ?? $deal->created_at,
                        'description' => 'Proposta enviada',
                        'data' => [
                            'service_type' => $proposal->serviceType->label ?? null,
                            'price' => $proposal->price,
                        ],
                    ]);
                }
            }
        }

        // Adiciona criação do cliente
        $timeline->push([
            'type' => 'client_created',
            'date' => $client->created_at,
            'description' => 'Cliente cadastrado',
            'data' => [],
        ]);

        // Adiciona contratos
        foreach ($client->contracts as $contract) {
            $timeline->push([
                'type' => 'contract_created',
                'date' => $contract->created_at ?? $contract->signed_at,
                'description' => 'Contrato ' . ($contract->status->label ?? 'criado'),
                'data' => [
                    'status' => $contract->status->code ?? null,
                    'start_date' => $contract->start_date?->toDateString(),
                    'end_date' => $contract->end_date?->toDateString(),
                ],
            ]);

            if ($contract->signed_at) {
                $timeline->push([
                    'type' => 'contract_signed',
                    'date' => $contract->signed_at,
                    'description' => 'Contrato assinado',
                    'data' => [],
                ]);
            }
        }

        // Adiciona consentimentos
        foreach ($client->consents as $consent) {
            $timeline->push([
                'type' => 'consent_granted',
                'date' => $consent->granted_at,
                'description' => 'Consentimento: ' . $consent->getTypeLabel(),
                'data' => [
                    'type' => $consent->consent_type,
                    'source' => $consent->source,
                ],
            ]);
        }

        // Ordena por data
        $timeline = $timeline->sortByDesc('date')->values();

        return [
            'client' => $client,
            'timeline' => $timeline,
        ];
    }

    /**
     * Exporta dados do cliente (LGPD)
     */
    public function exportClientData(Client $client): array
    {
        $client->load([
            'lead',
            'careNeeds.patientType',
            'contracts.proposal',
            'consents',
        ]);

        return [
            'personal_data' => [
                'name' => $client->lead?->name,
                'phone' => $client->phone,
                'email' => $client->lead?->email,
                'address' => $client->address,
                'city' => $client->city,
                'primary_contact' => $client->primary_contact,
            ],
            'preferences' => $client->preferences_json,
            'care_needs' => $client->careNeeds->map(fn($need) => [
                'patient_type' => $need->patientType->label ?? null,
                'conditions' => $need->conditions_json,
                'notes' => $need->notes,
            ]),
            'contracts' => $client->contracts->map(fn($contract) => [
                'start_date' => $contract->start_date?->toDateString(),
                'end_date' => $contract->end_date?->toDateString(),
                'status' => $contract->status->label ?? null,
            ]),
            'consents' => $client->consents->map(fn($consent) => [
                'type' => $consent->consent_type,
                'granted_at' => $consent->granted_at->toIso8601String(),
                'source' => $consent->source,
            ]),
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Anonimiza dados do cliente (LGPD - direito ao esquecimento)
     */
    public function anonymizeClient(Client $client): bool
    {
        return DB::transaction(function () use ($client) {
            // Anonimiza dados pessoais
            $client->update([
                'primary_contact' => 'ANONIMIZADO',
                'phone' => 'ANONIMIZADO',
                'address' => null,
                'preferences_json' => null,
            ]);

            // Anonimiza lead associado
            if ($client->lead) {
                $client->lead->update([
                    'name' => 'ANONIMIZADO',
                    'phone' => 'ANONIMIZADO',
                    'email' => null,
                ]);
            }

            // Remove consentimentos
            $client->consents()->delete();

            Log::channel('audit')->info('Cliente anonimizado (LGPD)', [
                'client_id' => $client->id,
            ]);

            return true;
        });
    }
}
