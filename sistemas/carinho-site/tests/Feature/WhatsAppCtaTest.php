<?php

namespace Tests\Feature;

use Tests\TestCase;

class WhatsAppCtaTest extends TestCase
{
    public function test_daily_quote_cta_redirects_with_matching_conversation_text(): void
    {
        $response = $this->get(route('whatsapp.cta', ['msg' => 'quote_diario']));

        $response->assertRedirect();
        $location = urldecode((string) $response->headers->get('Location'));

        $this->assertStringContainsString('https://wa.me/5589999771471', $location);
        $this->assertStringContainsString(
            'Gostaria de solicitar um orçamento para a diária de um cuidador.',
            $location
        );
    }

    public function test_hourly_and_monthly_quotes_use_distinct_texts(): void
    {
        $horista = urldecode((string) $this->get(route('whatsapp.cta', ['msg' => 'quote_horista']))->headers->get('Location'));
        $mensal = urldecode((string) $this->get(route('whatsapp.cta', ['msg' => 'quote_mensal']))->headers->get('Location'));

        $this->assertStringContainsString('orçamento para um cuidador por hora', $horista);
        $this->assertStringContainsString('orçamento para um cuidador mensal', $mensal);
        $this->assertStringNotContainsString('diária', $horista);
        $this->assertStringNotContainsString('diária', $mensal);
    }

    public function test_unknown_key_falls_back_to_default_message(): void
    {
        $response = $this->get(route('whatsapp.cta', ['msg' => 'chave_inventada']));
        $location = urldecode((string) $response->headers->get('Location'));

        $this->assertStringContainsString(config('branding.whatsapp_messages.default'), $location);
        $this->assertStringNotContainsString('chave_inventada', $location);
    }

    public function test_arbitrary_text_query_is_not_injected_into_whatsapp(): void
    {
        $response = $this->get('/whatsapp?text=' . urlencode('Mensagem maliciosa injetada'));
        $location = urldecode((string) $response->headers->get('Location'));

        $this->assertStringNotContainsString('Mensagem maliciosa injetada', $location);
        $this->assertStringContainsString(config('branding.whatsapp_messages.default'), $location);
    }

    public function test_services_page_wires_each_quote_button_to_its_message_key(): void
    {
        $html = $this->get(route('services'))->assertOk()->getContent();

        $this->assertStringContainsString(route('whatsapp.cta', ['msg' => 'quote_horista']), $html);
        $this->assertStringContainsString(route('whatsapp.cta', ['msg' => 'quote_diario']), $html);
        $this->assertStringContainsString(route('whatsapp.cta', ['msg' => 'quote_mensal']), $html);
        $this->assertStringContainsString(route('whatsapp.cta', ['msg' => 'quote']), $html);
        $this->assertStringContainsString(route('whatsapp.cta', ['msg' => 'hire']), $html);
        $this->assertStringContainsString('Solicitar orçamento diário', $html);
        $this->assertStringContainsString('Solicitar orçamento por hora', $html);
        $this->assertStringContainsString('Solicitar orçamento mensal', $html);
    }

    public function test_contact_and_how_it_works_ctas_use_action_specific_keys(): void
    {
        $contact = $this->get(route('contact'))->assertOk()->getContent();
        $this->assertStringContainsString(route('whatsapp.cta', ['msg' => 'contact']), $contact);

        $how = $this->get(route('how-it-works'))->assertOk()->getContent();
        $this->assertStringContainsString(route('whatsapp.cta', ['msg' => 'how_it_works']), $how);

        $clients = $this->get(route('clients'))->assertOk()->getContent();
        $this->assertStringContainsString(route('whatsapp.cta', ['msg' => 'quote']), $clients);
    }
}
