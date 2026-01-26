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
        'primary' => '#5BBFAD',      // Verde suave - confianca, cuidado
        'primary_dark' => '#4AA99A', // Verde escuro
        'primary_light' => '#8DD4C7', // Verde claro
        'secondary' => '#F4F7F9',    // Cinza claro - neutralidade
        'accent' => '#F5C6AA',       // Pessego - calor humano
        'accent_dark' => '#E5A880',  // Pessego escuro
        'text' => '#1F2933',         // Cinza escuro - legibilidade
        'text_light' => '#616E7C',   // Cinza medio
        'text_muted' => '#9AA5B1',   // Cinza suave
        'background' => '#FFFFFF',   // Branco - limpeza
        'background_alt' => '#F9FAFB', // Cinza muito claro
        'success' => '#38A169',      // Verde - confirmacao
        'warning' => '#D69E2E',      // Amarelo - atencao
        'danger' => '#E53E3E',       // Vermelho - erro
        'info' => '#3182CE',         // Azul - informacao
        'border' => '#E4E7EB',       // Cinza borda
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
        'font_family' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
    ],
    'assets' => [
        'logo' => [
            'primary' => '/images/logo-primary.png',
            'white' => '/images/logo-white.png',
            'icon' => '/images/logo-icon.png',
            'favicon' => '/images/favicon.ico',
        ],
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
