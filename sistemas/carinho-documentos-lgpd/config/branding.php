<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Identidade da Marca
    |--------------------------------------------------------------------------
    |
    | Configuracoes de branding da Carinho com Voce.
    | Tom de voz: empatico, respeitoso e objetivo.
    | Linguagem: simples, sem jargoes tecnicos.
    |
    */

    'name' => env('BRAND_NAME', 'Carinho com Voce'),
    'domain' => env('BRAND_DOMAIN', 'carinho.com.vc'),
    'subdomain' => env('DOCUMENTOS_SUBDOMAIN', 'documentos.carinho.com.vc'),

    'voice' => [
        'tone' => 'empatico, respeitoso e objetivo',
        'language' => 'simples e direta',
        'avoid' => 'termos que infantilizem o idoso ou o cuidador',
    ],

    /*
    |--------------------------------------------------------------------------
    | Paleta de Cores
    |--------------------------------------------------------------------------
    |
    | Tons suaves que transmitem confianca e calma.
    |
    */
    'colors' => [
        'primary' => '#5BBFAD',
        'primary_dark' => '#4AA89A',
        'primary_light' => '#7ACFC0',
        'secondary' => '#F4F7F9',
        'accent' => '#F5C6AA',
        'text' => '#1F2933',
        'text_muted' => '#616E7C',
        'success' => '#38A169',
        'warning' => '#D69E2E',
        'danger' => '#E53E3E',
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

    /*
    |--------------------------------------------------------------------------
    | Brand Assets
    |--------------------------------------------------------------------------
    */
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
    | Email
    |--------------------------------------------------------------------------
    */
    'email' => [
        'signature_name' => env('BRAND_SIGNATURE_NAME', 'Equipe Carinho'),
        'from' => env('DOCUMENTOS_EMAIL_FROM', 'documentos@carinho.com.vc'),
        'reply_to' => env('BRAND_REPLY_TO', 'contato@carinho.com.vc'),
        'privacy' => env('PRIVACY_EMAIL', 'privacidade@carinho.com.vc'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mensagens Padrao
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'contract_ready' => 'Seu contrato esta pronto para assinatura. Acesse o link para revisar e assinar.',
        'contract_signed' => 'Contrato assinado com sucesso! Uma copia foi enviada para seu email.',
        'document_uploaded' => 'Documento recebido com sucesso. Em breve sera processado.',
        'consent_recorded' => 'Seu consentimento foi registrado com sucesso.',
        'consent_revoked' => 'Seu consentimento foi revogado conforme solicitado.',
        'data_export_ready' => 'Sua exportacao de dados esta pronta para download.',
        'data_deleted' => 'Seus dados foram excluidos conforme solicitado.',
    ],
];
