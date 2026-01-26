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
    'subdomain' => env('SITE_SUBDOMAIN', 'site.carinho.com.vc'),

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

    'value_proposition' => 'Contratacao rapida e sem complicacao de cuidadores qualificados, com atendimento humanizado e gestao digital.',

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
        'font_family' => 'Inter, "Nunito", Arial, sans-serif',
        'font_family_headings' => '"Nunito", Inter, Arial, sans-serif',
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
        'og_image' => '/images/og-image.jpg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Information
    |--------------------------------------------------------------------------
    */

    'contact' => [
        'whatsapp' => env('BRAND_WHATSAPP', '5511999999999'),
        'whatsapp_display' => env('BRAND_WHATSAPP_DISPLAY', '(11) 99999-9999'),
        'email' => env('BRAND_EMAIL', 'contato@carinho.com.vc'),
        'email_privacy' => env('BRAND_EMAIL_PRIVACY', 'privacidade@carinho.com.vc'),
        'email_emergency' => env('BRAND_EMAIL_EMERGENCY', 'emergencia@carinho.com.vc'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media
    |--------------------------------------------------------------------------
    */

    'social' => [
        'instagram' => env('BRAND_INSTAGRAM', 'https://instagram.com/carinhocomvoce'),
        'facebook' => env('BRAND_FACEBOOK', 'https://facebook.com/carinhocomvoce'),
        'linkedin' => env('BRAND_LINKEDIN', 'https://linkedin.com/company/carinhocomvoce'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp CTA Messages
    |--------------------------------------------------------------------------
    */

    'whatsapp_messages' => [
        'default' => 'Ola! Vim pelo site e gostaria de saber mais sobre os servicos.',
        'client' => 'Ola! Preciso contratar um cuidador.',
        'caregiver' => 'Ola! Sou cuidador(a) e gostaria de me cadastrar.',
        'urgent' => 'Ola! Preciso de um cuidador urgente!',
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Defaults
    |--------------------------------------------------------------------------
    */

    'seo' => [
        'title_suffix' => ' | Carinho com Voce',
        'default_title' => 'Carinho com Voce - Cuidadores Domiciliares Qualificados',
        'default_description' => 'Encontre cuidadores qualificados para idosos e pessoas com necessidades especiais. Contratacao rapida, segura e sem complicacao. Atendimento humanizado.',
        'default_keywords' => 'cuidador de idosos, cuidado domiciliar, home care, cuidador profissional, acompanhante de idosos, cuidador qualificado',
    ],
];
