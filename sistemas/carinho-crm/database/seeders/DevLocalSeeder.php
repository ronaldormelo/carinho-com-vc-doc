<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Dados apenas de desenvolvimento local. Nao usar em producao.
 */
class DevLocalSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'administrador@carinho.com.vc';

    public const SIGN_TOKEN = 'dev-local-sign-token';

    public function run(): void
    {
        $password = env('CRM_ADMIN_PASSWORD', 'ChangeMeLocal!');

        $user = User::query()->firstOrNew(['email' => self::ADMIN_EMAIL]);
        $user->name = $user->name ?: 'Administrador local';
        $user->password = Hash::make($password);
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->save();

        $lead = Lead::query()->orderBy('id')->first();
        if (!$lead) {
            $this->command?->warn('Sem leads: contrato de aceite nao foi seedado.');
            return;
        }

        $client = Client::query()->firstOrCreate(
            ['lead_id' => $lead->id],
            [
                'primary_contact' => $lead->name,
                'phone' => $lead->phone,
                'city' => $lead->city ?: 'Teresina',
            ]
        );

        $deal = Deal::query()->firstOrCreate(
            ['lead_id' => $lead->id],
            [
                'stage_id' => 1,
                'value_estimated' => 0,
                'status_id' => 1,
            ]
        );

        $proposal = Proposal::query()->firstOrCreate(
            ['deal_id' => $deal->id],
            [
                'service_type_id' => $lead->service_type_id ?: 1,
                'price' => 0,
                'notes' => 'Proposta local de aceite digital',
            ]
        );

        $contract = Contract::query()->firstOrCreate(
            ['proposal_id' => $proposal->id],
            [
                'client_id' => $client->id,
                'status_id' => 1,
                'start_date' => now()->toDateString(),
            ]
        );

        Cache::put(
            'contract_signature:' . self::SIGN_TOKEN,
            [
                'contract_id' => $contract->id,
                'created_at' => now()->toIso8601String(),
            ],
            now()->addDays(30)
        );

        $this->command?->info('Admin local: ' . self::ADMIN_EMAIL . ' / senha CRM_ADMIN_PASSWORD (default ChangeMeLocal!)');
        $this->command?->info('Aceite digital: GET /contract/' . self::SIGN_TOKEN . '/sign');
    }
}
