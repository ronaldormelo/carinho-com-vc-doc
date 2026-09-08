<?php

return [
    'name' => env('BRAND_NAME', 'Carinho com Você'),
    'domain' => env('BRAND_DOMAIN', 'carinho.com.vc'),
    'subdomain' => env('CUIDADORES_SUBDOMAIN', 'cuidadores.carinho.com.vc'),
    'voice' => [
        'tone' => 'empático, respeitoso e objetivo',
        'language' => 'simples e direta',
    ],
    'colors' => [
        'primary' => '#5BBFAD',
        'secondary' => '#F4F7F9',
        'accent' => '#F5C6AA',
        'text' => '#1a2b32',
        'success' => '#38A169',
        'warning' => '#D69E2E',
        'danger' => '#E53E3E',
    ],
    'typography' => [
        'font_family' => 'Inter, "Nunito", Arial, sans-serif',
        'font_family_headings' => '"Nunito", Inter, Arial, sans-serif',
    ],
    'assets' => [
        'logo' => [
            'primary' => '/images/logo-transparente.webp',
            'white' => '/images/logo-white.webp',
            'icon' => '/images/logo-icon.webp',
            'favicon' => '/images/favicon.ico',
        ],
    ],
    'email' => [
        'signature_name' => env('BRAND_SIGNATURE_NAME', 'Equipe Carinho'),
        'reply_to' => env('BRAND_REPLY_TO', 'contato@carinho.com.vc'),
        'cuidadores_from' => env('CUIDADORES_EMAIL_FROM', 'cuidadores@carinho.com.vc'),
    ],
    'messages' => [
        'welcome' => 'Bem-vindo(a) a Carinho com Você! Estamos felizes em tê-lo(a) como parte da nossa equipe de cuidadores.',
        'activation' => 'Parabéns! Seu cadastro foi aprovado e você já pode receber oportunidades de serviço.',
        'document_pending' => 'Seus documentos estão em análise. Em breve entraremos em contato.',
        'document_approved' => 'Documento aprovado com sucesso!',
        'document_rejected' => 'Infelizmente seu documento foi recusado. Por favor, envie novamente.',
    ],
];
