<?php

namespace Database\Seeders;

use App\Models\Domain\DomainChannelStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            ['name' => 'Facebook', 'status_id' => DomainChannelStatus::ACTIVE],
            ['name' => 'Instagram', 'status_id' => DomainChannelStatus::ACTIVE],
            ['name' => 'Meta Ads', 'status_id' => DomainChannelStatus::ACTIVE],
            ['name' => 'Google Ads', 'status_id' => DomainChannelStatus::ACTIVE],
            ['name' => 'WhatsApp', 'status_id' => DomainChannelStatus::ACTIVE],
            ['name' => 'LinkedIn', 'status_id' => DomainChannelStatus::INACTIVE],
            ['name' => 'Twitter', 'status_id' => DomainChannelStatus::INACTIVE],
            ['name' => 'TikTok', 'status_id' => DomainChannelStatus::INACTIVE],
            ['name' => 'YouTube', 'status_id' => DomainChannelStatus::INACTIVE],
            ['name' => 'Google Meu Negocio', 'status_id' => DomainChannelStatus::ACTIVE],
        ];

        foreach ($channels as $channel) {
            DB::table('marketing_channels')->updateOrInsert(
                ['name' => $channel['name']],
                $channel
            );
        }
    }
}
