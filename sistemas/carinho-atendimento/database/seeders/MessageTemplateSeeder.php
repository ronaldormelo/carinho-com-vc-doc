<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->toDateTimeString();

        $templates = [
            [
                'template_key' => 'first_response',
                'body' => 'Ola, obrigado por falar com a Carinho. Vou entender sua necessidade para indicar o cuidador ideal. Em instantes um atendente continua com voce.',
                'language' => 'pt-BR',
            ],
            [
                'template_key' => 'after_hours',
                'body' => 'Ola, nosso atendimento funciona de 08:00 a 18:00. Registramos sua mensagem e retornaremos assim que possivel.',
                'language' => 'pt-BR',
            ],
            [
                'template_key' => 'feedback_request',
                'body' => 'Como foi o atendimento? Sua avaliacao nos ajuda a melhorar. Responda com uma nota de 1 a 5.',
                'language' => 'pt-BR',
            ],
        ];

        foreach ($templates as $template) {
            $templateId = DB::table('message_templates')->insertGetId([
                'template_key' => $template['template_key'],
                'body' => $template['body'],
                'language' => $template['language'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('auto_rules')->insert([
                'trigger_key' => $template['template_key'],
                'template_id' => $templateId,
                'enabled' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
