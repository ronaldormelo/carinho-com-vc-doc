<?php

namespace Tests\Unit;

use App\Models\Domain\DomainServiceType;
use App\Services\WhatsAppService;
use Tests\TestCase;

class WhatsAppServiceCtaTest extends TestCase
{
    private WhatsAppService $whatsApp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->whatsApp = new WhatsAppService();
    }

    public function test_quote_diario_message_matches_daily_budget_action(): void
    {
        $message = $this->whatsApp->resolveCtaMessage('quote_diario');

        $this->assertSame(
            'Olá! Gostaria de solicitar um orçamento para a diária de um cuidador.',
            $message
        );
    }

    public function test_each_service_type_has_a_distinct_quote_message(): void
    {
        $horista = $this->whatsApp->resolveCtaMessage('quote_horista');
        $diario = $this->whatsApp->resolveCtaMessage('quote_diario');
        $mensal = $this->whatsApp->resolveCtaMessage('quote_mensal');

        $this->assertStringContainsString('por hora', $horista);
        $this->assertStringContainsString('diária', $diario);
        $this->assertStringContainsString('mensal', $mensal);
        $this->assertNotSame($horista, $diario);
        $this->assertNotSame($diario, $mensal);
        $this->assertNotSame($horista, $mensal);
    }

    public function test_unknown_or_invalid_keys_fall_back_to_default(): void
    {
        $default = config('branding.whatsapp_messages.default');

        $this->assertSame($default, $this->whatsApp->resolveCtaMessage(null));
        $this->assertSame($default, $this->whatsApp->resolveCtaMessage(''));
        $this->assertSame($default, $this->whatsApp->resolveCtaMessage('nao_existe'));
        $this->assertSame($default, $this->whatsApp->resolveCtaMessage('QUOTE_DIARIO'));
        $this->assertSame($default, $this->whatsApp->resolveCtaMessage('../etc/passwd'));
    }

    public function test_client_lead_message_follows_selected_service_type(): void
    {
        $this->assertSame(
            config('branding.whatsapp_messages.quote_horista'),
            $this->whatsApp->resolveClientLeadMessage(DomainServiceType::HORISTA)
        );
        $this->assertSame(
            config('branding.whatsapp_messages.quote_diario'),
            $this->whatsApp->resolveClientLeadMessage(DomainServiceType::DIARIO)
        );
        $this->assertSame(
            config('branding.whatsapp_messages.quote_mensal'),
            $this->whatsApp->resolveClientLeadMessage(DomainServiceType::MENSAL)
        );
        $this->assertSame(
            config('branding.whatsapp_messages.client'),
            $this->whatsApp->resolveClientLeadMessage(null)
        );
    }

    public function test_route_keys_match_the_page_intent(): void
    {
        $this->assertSame('quote', $this->whatsApp->messageKeyForRoute('clients'));
        $this->assertSame('quote', $this->whatsApp->messageKeyForRoute('services'));
        $this->assertSame('caregiver', $this->whatsApp->messageKeyForRoute('caregivers'));
        $this->assertSame('contact', $this->whatsApp->messageKeyForRoute('contact'));
        $this->assertSame('faq', $this->whatsApp->messageKeyForRoute('faq'));
        $this->assertSame('how_it_works', $this->whatsApp->messageKeyForRoute('how-it-works'));
        $this->assertSame('default', $this->whatsApp->messageKeyForRoute('home'));
        $this->assertSame('default', $this->whatsApp->messageKeyForRoute(null));
    }

    public function test_redirect_url_encodes_the_action_message(): void
    {
        $url = $this->whatsApp->buildCtaRedirectUrl('quote_diario');

        $this->assertStringStartsWith('https://wa.me/5589999771471?text=', $url);
        $this->assertStringContainsString(
            urlencode('Gostaria de solicitar um orçamento para a diária de um cuidador.'),
            $url
        );
    }

    public function test_utm_origin_is_appended_to_the_conversation_text(): void
    {
        $url = $this->whatsApp->buildCtaRedirectUrl('quote', [
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'cuidadores',
        ]);

        $decoded = urldecode($url);
        $this->assertStringContainsString('[Origem: google / cpc / cuidadores]', $decoded);
    }

    public function test_configured_service_types_have_matching_quote_keys(): void
    {
        foreach (array_keys(config('site.service_types')) as $code) {
            $key = 'quote_' . $code;
            $this->assertArrayHasKey($key, config('branding.whatsapp_messages'));
            $this->assertNotSame(
                config('branding.whatsapp_messages.default'),
                $this->whatsApp->resolveCtaMessage($key)
            );
        }
    }
}
