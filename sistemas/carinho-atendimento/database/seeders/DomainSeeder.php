<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('domain_channel')->insert([
            ['id' => 1, 'code' => 'whatsapp', 'label' => 'WhatsApp'],
            ['id' => 2, 'code' => 'email', 'label' => 'Email'],
        ]);

        DB::table('domain_conversation_status')->insert([
            ['id' => 1, 'code' => 'new', 'label' => 'New'],
            ['id' => 2, 'code' => 'triage', 'label' => 'Triage'],
            ['id' => 3, 'code' => 'proposal', 'label' => 'Proposal'],
            ['id' => 4, 'code' => 'waiting', 'label' => 'Waiting'],
            ['id' => 5, 'code' => 'active', 'label' => 'Active'],
            ['id' => 6, 'code' => 'lost', 'label' => 'Lost'],
            ['id' => 7, 'code' => 'closed', 'label' => 'Closed'],
        ]);

        DB::table('domain_priority')->insert([
            ['id' => 1, 'code' => 'low', 'label' => 'Low'],
            ['id' => 2, 'code' => 'normal', 'label' => 'Normal'],
            ['id' => 3, 'code' => 'high', 'label' => 'High'],
            ['id' => 4, 'code' => 'urgent', 'label' => 'Urgent'],
        ]);

        DB::table('domain_message_direction')->insert([
            ['id' => 1, 'code' => 'inbound', 'label' => 'Inbound'],
            ['id' => 2, 'code' => 'outbound', 'label' => 'Outbound'],
        ]);

        DB::table('domain_message_status')->insert([
            ['id' => 1, 'code' => 'queued', 'label' => 'Queued'],
            ['id' => 2, 'code' => 'sent', 'label' => 'Sent'],
            ['id' => 3, 'code' => 'delivered', 'label' => 'Delivered'],
            ['id' => 4, 'code' => 'failed', 'label' => 'Failed'],
        ]);

        DB::table('domain_agent_role')->insert([
            ['id' => 1, 'code' => 'agent', 'label' => 'Agent'],
            ['id' => 2, 'code' => 'supervisor', 'label' => 'Supervisor'],
            ['id' => 3, 'code' => 'admin', 'label' => 'Admin'],
        ]);

        DB::table('domain_incident_severity')->insert([
            ['id' => 1, 'code' => 'low', 'label' => 'Low'],
            ['id' => 2, 'code' => 'medium', 'label' => 'Medium'],
            ['id' => 3, 'code' => 'high', 'label' => 'High'],
            ['id' => 4, 'code' => 'critical', 'label' => 'Critical'],
        ]);

        DB::table('domain_webhook_status')->insert([
            ['id' => 1, 'code' => 'pending', 'label' => 'Pending'],
            ['id' => 2, 'code' => 'processed', 'label' => 'Processed'],
            ['id' => 3, 'code' => 'failed', 'label' => 'Failed'],
        ]);
    }
}
