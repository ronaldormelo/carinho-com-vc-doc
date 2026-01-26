<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Brand Identity
    |--------------------------------------------------------------------------
    */

    'name' => env('BRAND_NAME', 'Carinho com Voce'),
    'domain' => env('BRAND_DOMAIN', 'carinho.com.vc'),
    'subdomain' => env('MARKETING_SUBDOMAIN', 'marketing.carinho.com.vc'),

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
        'tone' => 'empatico, respeitoso e objetivo',
        'language' => 'simples e direta',
        'guidelines' => [
            'Linguagem simples, sem jargoes tecnicos.',
            'Evitar termos que infantilizem o idoso ou o cuidador.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Key Messages
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'hiring' => 'Contratacao rapida e sem complicacao.',
        'caregivers' => 'Cuidadores qualificados e avaliados.',
        'service' => 'Atendimento digital com suporte humano.',
        'replacement' => 'Substituicao facilitada quando necessario.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Visual Elements - Colors
    |--------------------------------------------------------------------------
    | Tons suaves que transmitem confianca e calma.
    */

    'colors' => [
        'primary' => '#5BBFAD',      // Verde suave - confianca, cuidado
        'secondary' => '#F4F7F9',    // Cinza claro - neutralidade
        'accent' => '#F5C6AA',       // Pessego - calor humano
        'text' => '#1F2933',         // Cinza escuro - legibilidade
        'text_light' => '#616E7C',   // Cinza medio
        'background' => '#FFFFFF',   // Branco - limpeza
        'success' => '#38A169',      // Verde - confirmacao
        'warning' => '#D69E2E',      // Amarelo - atencao
        'danger' => '#E53E3E',       // Vermelho - erro
        'info' => '#3182CE',         // Azul - informacao
    ],

    /*
    |--------------------------------------------------------------------------
    | Visual Elements - Typography
    |--------------------------------------------------------------------------
    | Fonte sans-serif com alta legibilidade.
    */

    'typography' => [
        'font_family' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
        'font_family_headings' => '"Nunito", Arial, sans-serif',
        'font_size_base' => '16px',
        'line_height' => '1.5',
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
        'templates' => [
            'post_square' => '/assets/templates/post-square.psd',
            'post_story' => '/assets/templates/post-story.psd',
            'cover_facebook' => '/assets/templates/cover-facebook.psd',
            'email_header' => '/assets/templates/email-header.html',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */

    'email' => [
        'signature_name' => env('BRAND_SIGNATURE_NAME', 'Equipe Carinho'),
        'from' => env('MARKETING_EMAIL_FROM', 'marketing@carinho.com.vc'),
        'reply_to' => env('BRAND_REPLY_TO', 'contato@carinho.com.vc'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media Bio Standard
    |--------------------------------------------------------------------------
    */

    'social' => [
        'bio_template' => "Cuidado domiciliar profissional \nContratacao rapida e confiavel \nWhatsApp: (XX) XXXXX-XXXX \ncarinho.com.vc",
        'hashtags' => [
            '#CarinhoComVoce',
            '#CuidadoDomiciliar',
            '#CuidadorDeIdosos',
            '#IdososAtivos',
            '#ApoioFamiliar',
        ],
        'cta_default' => 'Fale com a gente pelo WhatsApp!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Guidelines
    |--------------------------------------------------------------------------
    */

    'content' => [
        'post_frequency' => '2 posts/semana',
        'themes' => [
            'prova_social' => 'Depoimentos e casos de sucesso',
            'servicos' => 'Apresentacao dos servicos',
            'urgencia' => 'Disponibilidade e facilidade',
            'educativo' => 'Dicas de cuidado e bem-estar',
            'institucional' => 'Valores e equipe',
        ],
    ],
];
