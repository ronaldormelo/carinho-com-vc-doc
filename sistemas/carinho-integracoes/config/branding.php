<?php

/**
 * Configuracoes de identidade visual da marca Carinho com Voce.
 *
 * Paleta de cores suaves que transmitem confianca e calma.
 * Tipografia sans-serif com alta legibilidade.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Nome da Marca
    |--------------------------------------------------------------------------
    */

    'name' => 'Carinho com Você',
    'tagline' => 'Cuidado que faz a diferença',

    /*
    |--------------------------------------------------------------------------
    | Dominio
    |--------------------------------------------------------------------------
    */

    'domain' => 'carinho.com.vc',
    'subdomain' => 'integracoes.carinho.com.vc',

    /*
    |--------------------------------------------------------------------------
    | Paleta de Cores
    |--------------------------------------------------------------------------
    |
    | Tons suaves que transmitem confianca e calma.
    |
    */

    'colors' => [
        'primary' => '#5BBFAD',      // Verde suave - confianca, cuidado
        'primary_dark' => '#4AA99A', // Verde escuro
        'primary_light' => '#8DD4C7', // Verde claro
        'secondary' => '#F4F7F9',    // Cinza claro - neutralidade
        'accent' => '#F5C6AA',       // Pessego - calor humano
        'accent_dark' => '#E5A880',  // Pessego escuro
        'text' => '#1a2b32',         // Cinza escuro - legibilidade
        'text_light' => '#616E7C',   // Cinza medio
        'text_muted' => '#9AA5B1',   // Cinza suave
        'background' => '#FFFFFF',   // Branco - limpeza
        'background_alt' => '#F9FAFB', // Cinza muito claro
        'success' => '#38A169',      // Verde - confirmacao
        'warning' => '#D69E2E',      // Amarelo - atencao
        'danger' => '#E53E3E',       // Vermelho - erro
        'info' => '#3182CE',         // Azul - informacao
        'border' => '#E4E7EB',       // Cinza borda
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipografia
    |--------------------------------------------------------------------------
    |
    | Fonte sans-serif com alta legibilidade.
    |
    */

    'typography' => [
        'font_family' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
    ],
    'assets' => [
        'logo' => [
            'primary' => '/images/logo-transparente.webp',
            'white' => '/images/logo-white.webp',
            'icon' => '/images/logo-icon.webp',
            'favicon' => '/images/favicon.ico',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tom de Voz
    |--------------------------------------------------------------------------
    |
    | Empatico, respeitoso e objetivo.
    | Linguagem simples, sem jargoes tecnicos.
    |
    */

    'voice' => [
        'style' => 'empatico',
        'formality' => 'respeitoso',
        'clarity' => 'objetivo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mensagens Padrao
    |--------------------------------------------------------------------------
    |
    | Mensagens pre-definidas para automacoes.
    |
    */

    'messages' => [
        'welcome' => 'Olá! Bem-vindo(a) ao Carinho com Você. Estamos aqui para ajudar você a encontrar o cuidador ideal.',
        'lead_response' => 'Olá, {nome}! Recebemos seu contato e em breve um de nossos atendentes irá retornar. Obrigado por confiar no Carinho com Você!',
        'signup_welcome' => 'Seja bem-vindo(a), {nome}! Seu cadastro foi realizado com sucesso. Estamos felizes em ter você conosco.',
        'service_completed' => 'Olá, {nome}! O atendimento de hoje foi finalizado. Gostaríamos de saber como foi sua experiência.',
        'feedback_request' => 'Sua opinião é muito importante para nós! Avalie o atendimento de {cuidador} em uma escala de 1 a 5.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Templates
    |--------------------------------------------------------------------------
    */

    'email' => [
        'from_name' => 'Carinho com Você',
        'from_address' => 'naoresponda@carinho.com.vc',
        'support' => 'suporte@carinho.com.vc',
        'commercial' => 'comercial@carinho.com.vc',
    ],
];
