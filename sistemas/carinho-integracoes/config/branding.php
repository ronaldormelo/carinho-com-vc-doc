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
        'primary' => '#5B8C5A',      // Verde suave - confianca
        'secondary' => '#8FBC8F',    // Verde claro - tranquilidade
        'accent' => '#F5E6D3',       // Bege claro - acolhimento
        'background' => '#FAFAF8',   // Off-white - limpeza
        'text' => '#2C3E50',         // Cinza escuro - legibilidade
        'text_light' => '#7F8C8D',   // Cinza medio - secundario
        'success' => '#27AE60',      // Verde - sucesso
        'warning' => '#F39C12',      // Amarelo - atencao
        'danger' => '#E74C3C',       // Vermelho - erro
        'info' => '#3498DB',         // Azul - informacao
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
        'primary' => 'Inter, system-ui, sans-serif',
        'secondary' => 'Open Sans, system-ui, sans-serif',
        'monospace' => 'JetBrains Mono, monospace',
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
