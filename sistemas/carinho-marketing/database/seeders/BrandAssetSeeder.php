<?php

namespace Database\Seeders;

use App\Models\BrandAsset;
use Illuminate\Database\Seeder;

class BrandAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assets = [
            // Logos
            [
                'name' => 'Logo Principal',
                'type' => BrandAsset::TYPE_LOGO,
                'file_url' => '/assets/images/logo-primary.svg',
                'description' => 'Logo principal da marca em cores',
                'metadata' => ['format' => 'svg', 'usage' => 'primary'],
                'is_active' => true,
            ],
            [
                'name' => 'Logo Branco',
                'type' => BrandAsset::TYPE_LOGO,
                'file_url' => '/assets/images/logo-white.svg',
                'description' => 'Logo em branco para fundos escuros',
                'metadata' => ['format' => 'svg', 'usage' => 'dark-background'],
                'is_active' => true,
            ],
            [
                'name' => 'Logo Icone',
                'type' => BrandAsset::TYPE_LOGO,
                'file_url' => '/assets/images/logo-icon.svg',
                'description' => 'Icone/simbolo da marca para tamanhos pequenos',
                'metadata' => ['format' => 'svg', 'usage' => 'icon'],
                'is_active' => true,
            ],
            [
                'name' => 'Favicon',
                'type' => BrandAsset::TYPE_ICON,
                'file_url' => '/assets/images/favicon.ico',
                'description' => 'Favicon para navegadores',
                'metadata' => ['format' => 'ico', 'sizes' => '16x16,32x32'],
                'is_active' => true,
            ],

            // Templates
            [
                'name' => 'Template Post Quadrado',
                'type' => BrandAsset::TYPE_TEMPLATE,
                'file_url' => '/assets/templates/post-square.psd',
                'description' => 'Template para posts quadrados (1080x1080)',
                'metadata' => ['dimensions' => '1080x1080', 'format' => 'psd'],
                'is_active' => true,
            ],
            [
                'name' => 'Template Stories',
                'type' => BrandAsset::TYPE_TEMPLATE,
                'file_url' => '/assets/templates/post-story.psd',
                'description' => 'Template para stories (1080x1920)',
                'metadata' => ['dimensions' => '1080x1920', 'format' => 'psd'],
                'is_active' => true,
            ],
            [
                'name' => 'Template Capa Facebook',
                'type' => BrandAsset::TYPE_TEMPLATE,
                'file_url' => '/assets/templates/cover-facebook.psd',
                'description' => 'Template para capa do Facebook',
                'metadata' => ['dimensions' => '820x312', 'format' => 'psd'],
                'is_active' => true,
            ],
            [
                'name' => 'Template Email Header',
                'type' => BrandAsset::TYPE_TEMPLATE,
                'file_url' => '/assets/templates/email-header.html',
                'description' => 'Cabecalho padrao para emails',
                'metadata' => ['format' => 'html'],
                'is_active' => true,
            ],

            // Cores
            [
                'name' => 'Cor Primaria',
                'type' => BrandAsset::TYPE_COLOR,
                'file_url' => '#5BBFAD',
                'description' => 'Verde suave - confianca, cuidado',
                'metadata' => ['hex' => '#5BBFAD', 'rgb' => '91, 191, 173'],
                'is_active' => true,
            ],
            [
                'name' => 'Cor Secundaria',
                'type' => BrandAsset::TYPE_COLOR,
                'file_url' => '#F4F7F9',
                'description' => 'Cinza claro - neutralidade',
                'metadata' => ['hex' => '#F4F7F9', 'rgb' => '244, 247, 249'],
                'is_active' => true,
            ],
            [
                'name' => 'Cor Destaque',
                'type' => BrandAsset::TYPE_COLOR,
                'file_url' => '#F5C6AA',
                'description' => 'Pessego - calor humano',
                'metadata' => ['hex' => '#F5C6AA', 'rgb' => '245, 198, 170'],
                'is_active' => true,
            ],
        ];

        foreach ($assets as $asset) {
            BrandAsset::updateOrCreate(
                ['name' => $asset['name'], 'type' => $asset['type']],
                $asset
            );
        }
    }
}
