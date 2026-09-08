<?php

/**
 * Configuracoes de identidade visual da marca Carinho com Você.
 *
 * Segue os padroes definidos no arquivo "00 - Identidade da Marca.md"
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Brand Identity
    |--------------------------------------------------------------------------
    */

    'name' => env('BRAND_NAME', 'Carinho com Você'),
    'domain' => env('BRAND_DOMAIN', 'carinho.com.vc'),
    // Host publico do site: apex (nao mais site.carinho.com.vc).
    'subdomain' => env('SITE_SUBDOMAIN', env('BRAND_DOMAIN', 'carinho.com.vc')),

    /*
    |--------------------------------------------------------------------------
    | Brand Purpose
    |--------------------------------------------------------------------------
    */

    'purpose' => [
        'Tornar o cuidado domiciliar simples, humano e confiável.',
        'Reduzir o esforço da família para encontrar cuidadores qualificados.',
    ],

    'promise' => 'Atendimento rápido, transparente e com continuidade.',

    'value_proposition' => 'Contratação rápida e sem complicação de cuidadores qualificados, com atendimento humanizado e gestão digital.',

    /*
    |--------------------------------------------------------------------------
    | Brand Personality
    |--------------------------------------------------------------------------
    */

    'personality' => [
        'Humana e acolhedora',
        'Profissional e segura',
        'Simples e direta',
        'Confiável e responsável',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tone of Voice
    |--------------------------------------------------------------------------
    */

    'voice' => [
        'tone' => 'Empático, respeitoso e objetivo.',
        'language' => 'Linguagem simples, sem jargões técnicos.',
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
        'hiring' => 'Contratação rápida e sem complicação.',
        'caregivers' => 'Cuidadores qualificados e avaliados.',
        'service' => 'Atendimento digital com suporte humano.',
        'replacement' => 'Substituição facilitada quando necessário.',
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
        'whatsapp' => env('BRAND_WHATSAPP', '5589999771471'),
        'whatsapp_display' => env('BRAND_WHATSAPP_DISPLAY', '(89) 99977-1471'),
        'email' => env('BRAND_EMAIL', 'contato@carinho.com.vc'),
        'email_privacy' => env('BRAND_EMAIL_PRIVACY', 'privacidade@carinho.com.vc'),
        'email_emergency' => env('BRAND_EMAIL_EMERGENCY', 'emergencia@carinho.com.vc'),
        'email_investors' => env('BRAND_EMAIL_INVESTORS', 'investidores@carinho.com.vc'),
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
        'default' => 'Olá! Seja bem-vindo(a) à Carinho com Você. Preciso de cuidado domiciliar e gostaria de conversar.',
        'client' => 'Olá! Busco um cuidador para um familiar e preciso contratar.',
        'caregiver' => 'Olá! Sou cuidador(a) e gostaria de me cadastrar para trabalhar com a Carinho com Você.',
        'urgent' => 'Olá! Preciso de ajuda imediata com um atendimento. É urgente.',
        'quote' => 'Olá! Busco um cuidador para um familiar e gostaria de solicitar um orçamento.',
        'quote_horista' => 'Olá! Gostaria de solicitar um orçamento para um cuidador por hora.',
        'quote_diario' => 'Olá! Gostaria de solicitar um orçamento para a diária de um cuidador.',
        'quote_mensal' => 'Olá! Gostaria de solicitar um orçamento para um cuidador mensal.',
        'hire' => 'Olá! Gostaria de contratar um cuidador e receber uma proposta.',
        'need_caregiver' => 'Olá! Preciso de um cuidador e gostaria de receber um orçamento.',
        'contact' => 'Olá! Vim pelo site e gostaria de entrar em contato.',
        'faq' => 'Olá! Tenho uma dúvida e gostaria de mais informações sobre os serviços.',
        'how_it_works' => 'Olá! Gostaria de entender como funciona a contratação de um cuidador.',
        'about' => 'Olá! Gostaria de saber mais sobre a Carinho com Você.',
        'investor' => 'Olá! Tenho interesse comercial em parceria ou investimento na Carinho com Você.',
        'legal_privacy' => 'Olá! Tenho uma dúvida sobre a Política de Privacidade.',
        'legal_terms' => 'Olá! Tenho uma dúvida sobre os Termos de Uso.',
        'legal_cancellation' => 'Olá! Tenho uma dúvida sobre a Política de Cancelamento.',
        'legal_payment' => 'Olá! Tenho uma dúvida sobre a Política de Pagamento e Comissões.',
        'legal_caregiver_terms' => 'Olá! Tenho uma dúvida sobre os Termos para Cuidadores.',
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Defaults
    |--------------------------------------------------------------------------
    */

    'seo' => [
        'title_suffix' => ' | Carinho com Você',
        'default_title' => 'Carinho com Você - Cuidadores Domiciliares Qualificados',
        'default_description' => 'Encontre cuidadores qualificados para idosos e pessoas com necessidades especiais. Contratação rápida, segura e sem complicação. Atendimento humanizado.',
        'default_keywords' => 'cuidador de idosos, cuidado domiciliar, home care, cuidador profissional, acompanhante de idosos, cuidador qualificado',
    ],
];
