<?php

namespace Database\Seeders;

use App\Models\EventMapping;
use Illuminate\Database\Seeder;

class EventMappingSeeder extends Seeder
{
    /**
     * Cria mapeamentos de transformacao de eventos.
     */
    public function run(): void
    {
        $mappings = [
            // Lead criado -> CRM
            [
                'event_type' => 'lead.created',
                'target_system' => 'crm',
                'mapping' => [
                    'name' => 'name',
                    'phone' => 'phone',
                    'email' => 'email',
                    'source' => 'source',
                    'utm_source' => 'utm_source',
                    'utm_medium' => 'utm_medium',
                    'utm_campaign' => 'utm_campaign',
                    'message' => 'message',
                ],
            ],

            // Lead criado -> Marketing
            [
                'event_type' => 'lead.created',
                'target_system' => 'marketing',
                'mapping' => [
                    'lead_name' => 'name',
                    'lead_phone' => 'phone',
                    'lead_email' => 'email',
                    'source_channel' => 'source',
                    'campaign' => [
                        'type' => 'array',
                        'sources' => [
                            'source' => 'utm_source',
                            'medium' => 'utm_medium',
                            'campaign' => 'utm_campaign',
                        ],
                    ],
                ],
            ],

            // Cliente registrado -> CRM
            [
                'event_type' => 'client.registered',
                'target_system' => 'crm',
                'mapping' => [
                    'client_id' => 'id',
                    'name' => 'name',
                    'email' => 'email',
                    'phone' => 'phone',
                    'lead_id' => 'lead_id',
                    'service_type' => 'service_type',
                ],
            ],

            // Cliente registrado -> Financeiro
            [
                'event_type' => 'client.registered',
                'target_system' => 'financeiro',
                'mapping' => [
                    'crm_client_id' => 'id',
                    'client_name' => 'name',
                    'client_email' => 'email',
                    'client_phone' => 'phone',
                    'cpf' => 'cpf',
                ],
            ],

            // Servico agendado -> Operacao
            [
                'event_type' => 'service.scheduled',
                'target_system' => 'operacao',
                'mapping' => [
                    'client_id' => 'client_id',
                    'contract_id' => 'contract_id',
                    'service_type' => 'service_type',
                    'start_datetime' => 'start_date',
                    'end_datetime' => 'end_date',
                    'client_preferences' => 'preferences',
                ],
            ],

            // Servico completado -> Financeiro
            [
                'event_type' => 'service.completed',
                'target_system' => 'financeiro',
                'mapping' => [
                    'operacao_service_id' => 'id',
                    'client_id' => 'client_id',
                    'caregiver_id' => 'caregiver_id',
                    'service_type' => 'service_type',
                    'duration_hours' => 'duration_hours',
                    'completed_at' => 'completed_at',
                    'additionals' => [
                        'type' => 'array',
                        'sources' => [
                            'is_weekend' => 'is_weekend',
                            'is_night' => 'is_night',
                            'is_holiday' => 'is_holiday',
                        ],
                    ],
                ],
            ],

            // Servico completado -> CRM
            [
                'event_type' => 'service.completed',
                'target_system' => 'crm',
                'mapping' => [
                    'service_id' => 'id',
                    'client_id' => 'client_id',
                    'caregiver_id' => 'caregiver_id',
                    'completed_at' => 'completed_at',
                    'duration_hours' => 'duration_hours',
                ],
            ],

            // Pagamento recebido -> CRM
            [
                'event_type' => 'payment.received',
                'target_system' => 'crm',
                'mapping' => [
                    'payment_id' => 'id',
                    'invoice_id' => 'invoice_id',
                    'client_id' => 'client_id',
                    'amount' => 'amount',
                    'payment_method' => 'method',
                    'paid_at' => 'paid_at',
                ],
            ],

            // Repasse processado -> Cuidadores
            [
                'event_type' => 'payout.processed',
                'target_system' => 'cuidadores',
                'mapping' => [
                    'payout_id' => 'id',
                    'caregiver_id' => 'caregiver_id',
                    'amount' => 'amount',
                    'period_start' => 'period_start',
                    'period_end' => 'period_end',
                    'processed_at' => 'processed_at',
                ],
            ],

            // Feedback recebido -> Cuidadores
            [
                'event_type' => 'feedback.received',
                'target_system' => 'cuidadores',
                'mapping' => [
                    'service_id' => 'service_id',
                    'caregiver_id' => 'caregiver_id',
                    'client_id' => 'client_id',
                    'rating' => 'rating',
                    'comment' => 'comment',
                    'channel' => 'channel',
                ],
            ],

            // WhatsApp inbound -> Atendimento
            [
                'event_type' => 'whatsapp.inbound',
                'target_system' => 'atendimento',
                'mapping' => [
                    'phone' => 'phone',
                    'sender_name' => 'name',
                    'message_body' => 'body',
                    'lead_id' => 'lead_id',
                    'received_at' => 'received_at',
                ],
            ],
        ];

        foreach ($mappings as $mapping) {
            EventMapping::createVersion(
                $mapping['event_type'],
                $mapping['target_system'],
                $mapping['mapping']
            );
        }

        $this->command->info('Created ' . count($mappings) . ' event mappings.');
    }
}
