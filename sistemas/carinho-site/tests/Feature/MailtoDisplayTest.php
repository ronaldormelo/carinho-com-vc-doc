<?php

namespace Tests\Feature;

use Tests\TestCase;

class MailtoDisplayTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function publicPagesProvider(): array
    {
        return [
            'contato' => ['/contato'],
            'investidores' => ['/investidores'],
            'quem-somos' => ['/quem-somos'],
            'privacidade' => ['/legal/privacidade'],
            'termos' => ['/legal/termos'],
            'cancelamento' => ['/legal/cancelamento'],
            'pagamento' => ['/legal/pagamento'],
            'emergencias' => ['/legal/emergencias'],
            'termos-cuidador' => ['/legal/termos-cuidador'],
        ];
    }

    /**
     * @dataProvider publicPagesProvider
     */
    public function test_displayed_brand_emails_are_mailto_links(string $path): void
    {
        $response = $this->get($path);

        $response->assertOk();

        $html = $this->visibleHtml((string) $response->getContent());
        $emails = $this->brandEmails($html);

        $this->assertNotEmpty(
            $emails,
            "A página {$path} deveria exibir ao menos um e-mail de contato (footer)."
        );

        foreach ($emails as $email) {
            $this->assertTrue(
                str_contains($html, 'href="mailto:'.$email.'"'),
                "O e-mail {$email} em {$path} precisa de um link mailto."
            );
        }
    }

    public function test_contact_page_links_main_and_emergency_emails(): void
    {
        $response = $this->get('/contato');

        $response->assertOk();
        $response->assertSee('href="mailto:'.config('branding.contact.email').'"', false);
        $response->assertSee('href="mailto:'.config('branding.contact.email_emergency').'"', false);
        $response->assertSee('Enviar e-mail');
    }

    public function test_investors_page_links_investors_email(): void
    {
        $response = $this->get('/investidores');

        $response->assertOk();
        $response->assertSee('href="mailto:'.config('branding.contact.email_investors').'"', false);
    }

    private function visibleHtml(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;

        return $html;
    }

    /**
     * @return list<string>
     */
    private function brandEmails(string $html): array
    {
        preg_match_all('/[A-Z0-9._%+\-]+@carinho\.com\.vc/i', $html, $matches);

        return array_values(array_unique($matches[0]));
    }
}
