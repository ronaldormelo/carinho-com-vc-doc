<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->toDateTimeString();

        // Templates automáticos
        $autoTemplates = [
            [
                'template_key' => 'first_response',
                'body' => 'Olá! Obrigado por entrar em contato com a Carinho. Sou [NOME_ATENDENTE] e vou ajudá-lo a encontrar o cuidador ideal para sua família. Em instantes continuamos seu atendimento.',
                'language' => 'pt-BR',
            ],
            [
                'template_key' => 'after_hours',
                'body' => 'Olá! Nosso atendimento funciona de segunda a sexta, das 08:00 às 18:00, e aos sábados das 08:00 às 12:00. Registramos sua mensagem e retornaremos no próximo horário disponível. Para emergências com serviços já contratados, ligue para (XX) XXXX-XXXX.',
                'language' => 'pt-BR',
            ],
            [
                'template_key' => 'feedback_request',
                'body' => 'Olá! Como foi seu atendimento com a Carinho? Sua opinião nos ajuda a melhorar cada vez mais. Responda com uma nota de 1 a 5, sendo 5 excelente.',
                'language' => 'pt-BR',
            ],
            [
                'template_key' => 'proposal_sent',
                'body' => 'Olá [NOME_CONTATO]! Enviamos a proposta para seu e-mail. Qualquer dúvida sobre valores ou condições, estou à disposição aqui mesmo.',
                'language' => 'pt-BR',
            ],
            [
                'template_key' => 'waiting_response_reminder',
                'body' => 'Olá [NOME_CONTATO]! Passando para saber se teve a oportunidade de avaliar nossa proposta. Estou à disposição para esclarecer qualquer dúvida.',
                'language' => 'pt-BR',
            ],
            [
                'template_key' => 'service_confirmation',
                'body' => 'Olá [NOME_CONTATO]! Confirmando: o(a) cuidador(a) [NOME_CUIDADOR] iniciará o atendimento em [DATA] às [HORARIO]. Qualquer imprevisto, por favor nos avise com antecedência.',
                'language' => 'pt-BR',
            ],
        ];

        foreach ($autoTemplates as $template) {
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

        // Scripts de comunicação padronizados
        $scripts = [
            // Saudação (category_id = 1)
            [
                'code' => 'greeting_new_lead',
                'title' => 'Saudação - Novo Lead',
                'category_id' => 1,
                'support_level_id' => 1,
                'body' => "Olá, [NOME]! Tudo bem?\n\nSou [NOME_ATENDENTE] da Carinho Cuidadores. Recebi seu contato e ficarei feliz em ajudá-lo(a) a encontrar o cuidador ideal.\n\nPara começar, pode me contar um pouco sobre a pessoa que precisa de cuidados?",
                'variables_json' => '["NOME", "NOME_ATENDENTE"]',
                'usage_hint' => 'Usar no primeiro contato com leads novos',
                'display_order' => 1,
            ],
            [
                'code' => 'greeting_returning_client',
                'title' => 'Saudação - Cliente Retornando',
                'category_id' => 1,
                'support_level_id' => 1,
                'body' => "Olá, [NOME]! Que bom ter você de volta!\n\nComo posso ajudá-lo(a) hoje?",
                'variables_json' => '["NOME"]',
                'usage_hint' => 'Usar quando cliente já conhecido retorna contato',
                'display_order' => 2,
            ],

            // Qualificação (category_id = 2)
            [
                'code' => 'qualification_care_type',
                'title' => 'Qualificação - Tipo de Cuidado',
                'category_id' => 2,
                'support_level_id' => 1,
                'body' => "Entendi. Para indicar o cuidador mais adequado, preciso saber:\n\n1. Qual o tipo de cuidado necessário? (acompanhamento, higiene, medicação, etc.)\n2. O paciente tem alguma condição específica? (Alzheimer, AVC, diabetes, etc.)\n3. Ele(a) se locomove sozinho(a)?",
                'variables_json' => null,
                'usage_hint' => 'Usar para entender as necessidades do paciente',
                'display_order' => 1,
            ],
            [
                'code' => 'qualification_schedule',
                'title' => 'Qualificação - Horários',
                'category_id' => 2,
                'support_level_id' => 1,
                'body' => "Sobre os horários:\n\n1. Quantos dias por semana precisará do cuidador?\n2. Qual o horário? (manhã, tarde, noite, 12h, 24h)\n3. Há urgência para iniciar? Qual a data ideal?",
                'variables_json' => null,
                'usage_hint' => 'Usar para definir a escala de trabalho',
                'display_order' => 2,
            ],
            [
                'code' => 'qualification_location',
                'title' => 'Qualificação - Localização',
                'category_id' => 2,
                'support_level_id' => 1,
                'body' => "Para verificar disponibilidade de cuidadores na região:\n\nQual a cidade e bairro onde o serviço será realizado?",
                'variables_json' => null,
                'usage_hint' => 'Usar para verificar cobertura da região',
                'display_order' => 3,
            ],

            // Proposta (category_id = 3)
            [
                'code' => 'proposal_presentation',
                'title' => 'Apresentação de Proposta',
                'category_id' => 3,
                'support_level_id' => 2,
                'body' => "Com base nas suas necessidades, preparei uma proposta:\n\n*Tipo de serviço:* [TIPO_SERVICO]\n*Dias:* [DIAS]\n*Horário:* [HORARIO]\n*Valor:* R$ [VALOR]\n\nEste valor inclui:\n- Cuidador(a) capacitado(a) e com experiência\n- Acompanhamento da nossa equipe\n- Substituição em caso de falta\n- Suporte emergencial\n\nPosso enviar a proposta detalhada por e-mail?",
                'variables_json' => '["TIPO_SERVICO", "DIAS", "HORARIO", "VALOR"]',
                'usage_hint' => 'Usar para apresentar valores ao cliente',
                'display_order' => 1,
            ],
            [
                'code' => 'proposal_email_sent',
                'title' => 'Proposta Enviada por E-mail',
                'category_id' => 3,
                'support_level_id' => 2,
                'body' => "Pronto! Acabei de enviar a proposta completa para [EMAIL].\n\nNela você encontrará:\n- Detalhamento dos serviços\n- Valores e formas de pagamento\n- Contrato para assinatura digital\n\nQualquer dúvida, estou à disposição aqui mesmo!",
                'variables_json' => '["EMAIL"]',
                'usage_hint' => 'Usar após enviar proposta por e-mail',
                'display_order' => 2,
            ],

            // Objeção (category_id = 4)
            [
                'code' => 'objection_price',
                'title' => 'Objeção - Preço',
                'category_id' => 4,
                'support_level_id' => 2,
                'body' => "Entendo sua preocupação com o investimento. Nosso diferencial é a segurança e qualidade:\n\n- Cuidadores com verificação de antecedentes\n- Treinamento contínuo\n- Cobertura em caso de faltas\n- Acompanhamento da família\n\nPodemos avaliar outras opções de escala que se adequem melhor ao seu orçamento. O que acha de conversarmos sobre isso?",
                'variables_json' => null,
                'usage_hint' => 'Usar quando cliente questiona o preço',
                'display_order' => 1,
            ],
            [
                'code' => 'objection_think_about',
                'title' => 'Objeção - Preciso Pensar',
                'category_id' => 4,
                'support_level_id' => 2,
                'body' => "Claro, é uma decisão importante e entendo que precise avaliar com calma.\n\nFico à disposição para esclarecer qualquer dúvida que surja. Posso entrar em contato novamente em [PRAZO] para conversarmos?",
                'variables_json' => '["PRAZO"]',
                'usage_hint' => 'Usar quando cliente pede tempo para pensar',
                'display_order' => 2,
            ],
            [
                'code' => 'objection_competitor',
                'title' => 'Objeção - Concorrência',
                'category_id' => 4,
                'support_level_id' => 2,
                'body' => "É sempre bom comparar opções. Alguns pontos que nos diferenciam:\n\n- [ANOS] anos de experiência no mercado\n- Processo rigoroso de seleção de cuidadores\n- Acompanhamento contínuo do serviço\n- Avaliações positivas de famílias atendidas\n\nPosso ajudar com mais alguma informação para sua decisão?",
                'variables_json' => '["ANOS"]',
                'usage_hint' => 'Usar quando cliente menciona concorrência',
                'display_order' => 3,
            ],

            // Fechamento (category_id = 5)
            [
                'code' => 'closing_contract',
                'title' => 'Fechamento - Contrato',
                'category_id' => 5,
                'support_level_id' => 2,
                'body' => "Ótimo! Fico muito feliz em poder ajudar sua família.\n\nPróximos passos:\n1. Enviarei o contrato por e-mail para assinatura digital\n2. Após a assinatura, iniciaremos a seleção do cuidador ideal\n3. Apresentaremos o perfil do cuidador para sua aprovação\n4. Agendaremos o início do serviço\n\nPode confirmar o e-mail para envio do contrato?",
                'variables_json' => null,
                'usage_hint' => 'Usar quando cliente decide fechar o serviço',
                'display_order' => 1,
            ],
            [
                'code' => 'closing_caregiver_presentation',
                'title' => 'Fechamento - Apresentação do Cuidador',
                'category_id' => 5,
                'support_level_id' => 2,
                'body' => "Encontramos o(a) cuidador(a) ideal para sua família!\n\n*Nome:* [NOME_CUIDADOR]\n*Experiência:* [EXPERIENCIA]\n*Formação:* [FORMACAO]\n\nEle(a) tem disponibilidade para iniciar em [DATA]. Podemos agendar uma conversa prévia se preferir.\n\nO que acha?",
                'variables_json' => '["NOME_CUIDADOR", "EXPERIENCIA", "FORMACAO", "DATA"]',
                'usage_hint' => 'Usar para apresentar cuidador selecionado',
                'display_order' => 2,
            ],

            // Suporte (category_id = 6)
            [
                'code' => 'support_absence_notification',
                'title' => 'Suporte - Falta de Cuidador',
                'category_id' => 6,
                'support_level_id' => 2,
                'body' => "Olá [NOME]!\n\nInfelizmente o(a) cuidador(a) [NOME_CUIDADOR] não poderá comparecer hoje devido a [MOTIVO].\n\nJá estamos providenciando um substituto qualificado. Em breve envio os dados do profissional que fará o atendimento.\n\nPeço desculpas pelo transtorno.",
                'variables_json' => '["NOME", "NOME_CUIDADOR", "MOTIVO"]',
                'usage_hint' => 'Usar para comunicar falta de cuidador',
                'display_order' => 1,
            ],
            [
                'code' => 'support_schedule_change',
                'title' => 'Suporte - Mudança de Horário',
                'category_id' => 6,
                'support_level_id' => 1,
                'body' => "Olá [NOME]!\n\nRegistrei sua solicitação de alteração de horário:\n- De: [HORARIO_ANTERIOR]\n- Para: [HORARIO_NOVO]\n\nVou verificar a disponibilidade e retorno em breve com a confirmação.",
                'variables_json' => '["NOME", "HORARIO_ANTERIOR", "HORARIO_NOVO"]',
                'usage_hint' => 'Usar para confirmar pedido de mudança de horário',
                'display_order' => 2,
            ],

            // Emergência (category_id = 7)
            [
                'code' => 'emergency_initial',
                'title' => 'Emergência - Contato Inicial',
                'category_id' => 7,
                'support_level_id' => 3,
                'body' => "Olá [NOME], recebi seu chamado de emergência.\n\nPor favor, descreva brevemente a situação para que eu possa acionar a equipe adequada imediatamente.\n\nSe for emergência médica, ligue para o SAMU (192) enquanto conversamos.",
                'variables_json' => '["NOME"]',
                'usage_hint' => 'Usar em situações de emergência',
                'display_order' => 1,
            ],
            [
                'code' => 'emergency_action_taken',
                'title' => 'Emergência - Ação Tomada',
                'category_id' => 7,
                'support_level_id' => 3,
                'body' => "Entendi a situação. Já tomei as seguintes providências:\n\n[ACOES_TOMADAS]\n\nContinuo acompanhando e mantenho você informado(a). Qualquer novidade, avise imediatamente.",
                'variables_json' => '["ACOES_TOMADAS"]',
                'usage_hint' => 'Usar para informar ações em emergência',
                'display_order' => 2,
            ],

            // Feedback (category_id = 8)
            [
                'code' => 'feedback_positive_response',
                'title' => 'Feedback - Resposta Positiva',
                'category_id' => 8,
                'support_level_id' => 1,
                'body' => "Que ótimo saber que está satisfeito(a) com nosso serviço! Sua avaliação significa muito para nós.\n\nSe puder nos avaliar no Google, ajudará outras famílias a nos encontrar: [LINK_AVALIACAO]\n\nObrigado por confiar na Carinho!",
                'variables_json' => '["LINK_AVALIACAO"]',
                'usage_hint' => 'Usar quando cliente dá feedback positivo',
                'display_order' => 1,
            ],
            [
                'code' => 'feedback_negative_response',
                'title' => 'Feedback - Resposta Negativa',
                'category_id' => 8,
                'support_level_id' => 2,
                'body' => "Lamento muito que sua experiência não tenha sido satisfatória. Sua opinião é muito importante para melhorarmos.\n\nPode me contar mais detalhes sobre o que aconteceu? Vou encaminhar para nossa equipe de qualidade e retorno com uma solução.",
                'variables_json' => null,
                'usage_hint' => 'Usar quando cliente reporta insatisfação',
                'display_order' => 2,
            ],
        ];

        foreach ($scripts as $script) {
            DB::table('communication_scripts')->insert([
                'code' => $script['code'],
                'title' => $script['title'],
                'category_id' => $script['category_id'],
                'support_level_id' => $script['support_level_id'],
                'body' => $script['body'],
                'variables_json' => $script['variables_json'],
                'usage_hint' => $script['usage_hint'],
                'display_order' => $script['display_order'],
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
