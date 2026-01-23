<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Identidade da Marca - Carinho com Você
    |--------------------------------------------------------------------------
    |
    | Configurações de identidade visual baseadas no documento
    | "00 - Identidade da Marca.txt"
    |
    */

    'name' => 'Carinho com Você',
    'domain' => 'carinho.com.vc',
    'subdomain' => 'financeiro.carinho.com.vc',

    /*
    |--------------------------------------------------------------------------
    | Propósito e Promessa
    |--------------------------------------------------------------------------
    */
    'purpose' => 'Tornar o cuidado domiciliar simples, humano e confiável.',
    'promise' => 'Atendimento rápido, transparente e com continuidade.',

    /*
    |--------------------------------------------------------------------------
    | Tom de Voz para Comunicações
    |--------------------------------------------------------------------------
    |
    | Empático, respeitoso e objetivo.
    | Linguagem simples, sem jargões técnicos.
    | Evitar termos que infantilizem o idoso ou o cuidador.
    |
    */
    'tone' => [
        'style' => 'empático, respeitoso e objetivo',
        'language' => 'simples, sem jargões técnicos',
        'avoid' => 'termos que infantilizem o idoso ou o cuidador',
    ],

    /*
    |--------------------------------------------------------------------------
    | Paleta de Cores
    |--------------------------------------------------------------------------
    |
    | Tons suaves que transmitem confiança e calma.
    |
    */
    'colors' => [
        'primary' => '#4A90A4',      // Azul calmo
        'secondary' => '#7BB5C4',    // Azul claro
        'accent' => '#E8B86D',       // Dourado suave
        'success' => '#6BAF7A',      // Verde suave
        'warning' => '#E8B86D',      // Dourado (igual accent)
        'danger' => '#D4736D',       // Vermelho suave
        'text' => '#3D4852',         // Cinza escuro
        'text_light' => '#6C757D',   // Cinza médio
        'background' => '#F8F9FA',   // Cinza muito claro
        'white' => '#FFFFFF',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipografia
    |--------------------------------------------------------------------------
    |
    | Fonte sans-serif, alta legibilidade.
    |
    */
    'typography' => [
        'primary' => "'Inter', 'Segoe UI', system-ui, sans-serif",
        'secondary' => "'Inter', 'Segoe UI', system-ui, sans-serif",
    ],

    /*
    |--------------------------------------------------------------------------
    | Mensagens Padrão para Comunicação Financeira
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'invoice_created' => 'Olá! Sua fatura está disponível. Acesse o link para visualizar e pagar: {link}',
        'payment_confirmed' => 'Pagamento confirmado! Obrigado pela confiança. Seu serviço está garantido.',
        'payment_reminder' => 'Lembrete: sua fatura vence em {days} dias. Evite juros pagando até o vencimento.',
        'payment_overdue' => 'Sua fatura está em atraso. Regularize para continuar utilizando nossos serviços.',
        'payout_processed' => 'Repasse realizado! O valor de R$ {amount} foi transferido para sua conta.',
        'cancellation_processed' => 'Cancelamento processado. O reembolso de R$ {amount} será creditado em até 5 dias úteis.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dados da Empresa para Documentos
    |--------------------------------------------------------------------------
    */
    'company' => [
        'name' => env('COMPANY_NAME', 'Carinho com Você Ltda'),
        'cnpj' => env('COMPANY_CNPJ'),
        'address' => env('COMPANY_ADDRESS'),
        'phone' => env('COMPANY_PHONE'),
        'email' => env('COMPANY_EMAIL', 'financeiro@carinho.com.vc'),
        'website' => 'https://carinho.com.vc',
    ],

    /*
    |--------------------------------------------------------------------------
    | Emails
    |--------------------------------------------------------------------------
    */
    'email' => [
        'from_address' => env('MAIL_FROM_ADDRESS', 'financeiro@carinho.com.vc'),
        'from_name' => env('MAIL_FROM_NAME', 'Carinho com Você'),
        'reply_to' => env('MAIL_REPLY_TO', 'contato@carinho.com.vc'),
    ],
];
