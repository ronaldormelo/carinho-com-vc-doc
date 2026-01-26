<?php

return [
    'name' => env('BRAND_NAME', 'Carinho com Voce'),
    'domain' => env('BRAND_DOMAIN', 'carinho.com.vc'),
    'subdomain' => env('CUIDADORES_SUBDOMAIN', 'cuidadores.carinho.com.vc'),
    'voice' => [
        'tone' => 'empatico, respeitoso e objetivo',
        'language' => 'simples e direta',
    ],
    'colors' => [
        'primary' => '#5BBFAD',
        'secondary' => '#F4F7F9',
        'accent' => '#F5C6AA',
        'text' => '#1F2933',
        'success' => '#38A169',
        'warning' => '#D69E2E',
        'danger' => '#E53E3E',
    ],
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
    'email' => [
        'signature_name' => env('BRAND_SIGNATURE_NAME', 'Equipe Carinho'),
        'reply_to' => env('BRAND_REPLY_TO', 'contato@carinho.com.vc'),
        'cuidadores_from' => env('CUIDADORES_EMAIL_FROM', 'cuidadores@carinho.com.vc'),
    ],
    'messages' => [
        'welcome' => 'Bem-vindo(a) a Carinho com Voce! Estamos felizes em te-lo(a) como parte da nossa equipe de cuidadores.',
        'activation' => 'Parabens! Seu cadastro foi aprovado e voce ja pode receber oportunidades de servico.',
        'document_pending' => 'Seus documentos estao em analise. Em breve entraremos em contato.',
        'document_approved' => 'Documento aprovado com sucesso!',
        'document_rejected' => 'Infelizmente seu documento foi recusado. Por favor, envie novamente.',
    ],
];
