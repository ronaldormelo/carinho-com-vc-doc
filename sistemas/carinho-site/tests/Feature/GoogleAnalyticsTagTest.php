<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleAnalyticsTagTest extends TestCase
{
    public function test_default_measurement_id_is_the_site_ga4_property(): void
    {
        $this->assertTrue((bool) config('integrations.analytics.enabled'));
        $this->assertSame('G-WLV8231QBM', config('integrations.analytics.ga4_id'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function publicPagesProvider(): array
    {
        return [
            'quem-somos' => ['/quem-somos'],
            'servicos' => ['/servicos'],
            'como-funciona' => ['/como-funciona'],
            'contato' => ['/contato'],
            'investidores' => ['/investidores'],
            'clientes' => ['/clientes'],
            'cuidadores' => ['/cuidadores'],
            'privacidade' => ['/legal/privacidade'],
            'termos' => ['/legal/termos'],
            'cancelamento' => ['/legal/cancelamento'],
            'pagamento' => ['/legal/pagamento'],
            'emergencias' => ['/legal/emergencias'],
            'termos-cuidador' => ['/legal/termos-cuidador'],
        ];
    }

    #[DataProvider('publicPagesProvider')]
    public function test_public_pages_include_ga4_gtag(string $path): void
    {
        config([
            'integrations.analytics.enabled' => true,
            'integrations.analytics.ga4_id' => 'G-WLV8231QBM',
        ]);

        $html = $this->get($path)->assertOk()->getContent();

        $this->assertStringContainsString(
            'https://www.googletagmanager.com/gtag/js?id=G-WLV8231QBM',
            $html
        );
        $this->assertStringContainsString("gtag('config', 'G-WLV8231QBM')", $html);
        $this->assertStringContainsString('window.dataLayer = window.dataLayer || []', $html);
    }

    public function test_gtag_is_omitted_when_analytics_is_disabled(): void
    {
        config([
            'integrations.analytics.enabled' => false,
            'integrations.analytics.ga4_id' => 'G-WLV8231QBM',
        ]);

        $html = $this->get('/contato')->assertOk()->getContent();

        $this->assertStringNotContainsString('gtag/js?id=', $html);
        $this->assertStringNotContainsString("gtag('config'", $html);
    }

    public function test_gtag_is_omitted_when_measurement_id_is_blank(): void
    {
        config([
            'integrations.analytics.enabled' => true,
            'integrations.analytics.ga4_id' => '',
        ]);

        $html = $this->get('/contato')->assertOk()->getContent();

        $this->assertStringNotContainsString('gtag/js?id=', $html);
        $this->assertStringNotContainsString("gtag('config'", $html);
    }

    public function test_health_endpoint_does_not_embed_gtag(): void
    {
        $json = $this->get('/health')->assertOk()->getContent();

        $this->assertStringNotContainsString('gtag/js', $json);
        $this->assertStringNotContainsString('G-WLV8231QBM', $json);
    }
}
