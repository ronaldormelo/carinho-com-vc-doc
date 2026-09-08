<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WhatsAppNumberDisplayTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function pagesWithVisibleNumberProvider(): array
    {
        return [
            'home' => ['/'],
            'contato' => ['/contato'],
            'clientes' => ['/clientes'],
            'cuidadores' => ['/cuidadores'],
            'investidores' => ['/investidores'],
            'faq' => ['/faq'],
            'privacidade' => ['/legal/privacidade'],
            'termos' => ['/legal/termos'],
            'cancelamento' => ['/legal/cancelamento'],
            'pagamento' => ['/legal/pagamento'],
            'emergencias' => ['/legal/emergencias'],
            'termos-cuidador' => ['/legal/termos-cuidador'],
        ];
    }

    #[DataProvider('pagesWithVisibleNumberProvider')]
    public function test_visible_whatsapp_number_is_an_anchor(string $path): void
    {
        $response = $this->get($path);
        $response->assertOk();

        $html = $this->visibleHtml((string) $response->getContent());
        $display = (string) config('branding.contact.whatsapp_display');

        $this->assertStringContainsString($display, $html);

        $offset = 0;
        $found = 0;
        while (($pos = strpos($html, $display, $offset)) !== false) {
            $found++;
            $before = substr($html, max(0, $pos - 500), 500);
            $lastOpen = strrpos($before, '<a');
            $lastClose = strrpos($before, '</a>');
            $this->assertNotFalse($lastOpen, "O número {$display} em {$path} não está em um link.");
            $this->assertTrue(
                $lastClose === false || $lastClose < $lastOpen,
                "O número {$display} em {$path} precisa estar dentro de um <a href> para WhatsApp."
            );
            $this->assertMatchesRegularExpression(
                '/<a\b[^>]*href="[^"]*whatsapp[^"]*"/s',
                substr($before, (int) $lastOpen),
                "O número {$display} em {$path} precisa de href para WhatsApp."
            );
            $offset = $pos + strlen($display);
        }

        $this->assertGreaterThan(0, $found);
    }

    public function test_home_footer_email_is_mailto(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $email = (string) config('branding.contact.email');

        $this->assertStringContainsString('href="mailto:'.$email.'"', $html);
    }

    private function visibleHtml(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;

        return $html;
    }
}
