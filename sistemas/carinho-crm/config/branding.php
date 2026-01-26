<?php

/**
 * Configuracoes de identidade visual da marca Carinho com Voce.
 *
 * Segue os padroes definidos no arquivo "00 - Identidade da Marca.txt"
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Brand Identity
    |--------------------------------------------------------------------------
    */

    'name' => env('BRAND_NAME', 'Carinho com Voce'),
    'domain' => env('BRAND_DOMAIN', 'carinho.com.vc'),
    'subdomain' => env('CRM_SUBDOMAIN', 'crm.carinho.com.vc'),

    /*
    |--------------------------------------------------------------------------
    | Brand Purpose
    |--------------------------------------------------------------------------
    */

    'purpose' => [
        'Tornar o cuidado domiciliar simples, humano e confiavel.',
        'Reduzir o esforco da familia para encontrar cuidadores qualificados.',
    ],

    'promise' => 'Atendimento rapido, transparente e com continuidade.',

    /*
    |--------------------------------------------------------------------------
    | Brand Personality
    |--------------------------------------------------------------------------
    */

    'personality' => [
        'Humana e acolhedora',
        'Profissional e segura',
        'Simples e direta',
        'Confiavel e responsavel',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tone of Voice
    |--------------------------------------------------------------------------
    */

    'voice' => [
        'tone' => 'Empatico, respeitoso e objetivo.',
        'language' => 'Linguagem simples, sem jargoes tecnicos.',
        'guidelines' => [
            'Evitar termos que infantilizem o idoso ou o cuidador.',
            'Respostas claras e objetivas.',
            'Linguagem humana e acolhedora.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Visual Elements - Colors
    |--------------------------------------------------------------------------
    | Tons suaves que transmitem confianca e calma.
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Visual Elements - Typography
    |--------------------------------------------------------------------------
    | Fonte sans-serif com alta legibilidade.
    */

    'typography' => [
        'font_family' => 'Inter, Arial, "Helvetica Neue", Helvetica, sans-serif',
        'font_family_headings' => 'Inter, Arial, sans-serif',
        'font_size_base' => '16px',
        'line_height' => '1.6',
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand Kit - Assets
    |--------------------------------------------------------------------------
    */

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
    | Email Configuration
    |--------------------------------------------------------------------------
    */

    'email' => [
        'signature_name' => env('BRAND_SIGNATURE_NAME', 'Equipe Carinho'),
        'from' => env('CRM_EMAIL_FROM', 'crm@carinho.com.vc'),
        'reply_to' => env('BRAND_REPLY_TO', 'contato@carinho.com.vc'),
    ],
];
