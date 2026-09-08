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

        $this->assertStringContainsString('whatsapp?msg=quote_horista', $html);
        $this->assertStringContainsString('whatsapp?msg=quote_diario', $html);
        $this->assertStringContainsString('whatsapp?msg=quote_mensal', $html);
        $this->assertMatchesRegularExpression('/whatsapp\?msg=quote["\']/', $html);
        $this->assertStringContainsString('whatsapp?msg=hire', $html);
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
        $this->assertMatchesRegularExpression('/whatsapp\?msg=quote["\']/', $clients);
        $this->assertStringContainsString('whatsapp?msg=quote', $clients);
    }

    public function test_header_and_float_on_contact_page_use_contact_message(): void
    {
        $html = $this->get(route('contact'))->assertOk()->getContent();
        $this->assertGreaterThanOrEqual(
            3,
            substr_count($html, 'whatsapp?msg=contact'),
            'Botão da página, header e flutuante devem usar a mensagem de contato'
        );
    }

    public function test_caregivers_page_does_not_reuse_family_hiring_copy(): void
    {
        $html = $this->get(route('caregivers'))->assertOk()->getContent();

        $this->assertStringContainsString('whatsapp?msg=caregiver', $html);
        $this->assertStringContainsString('whatsapp?msg=contact', $html);
        $this->assertDoesNotMatchRegularExpression('/whatsapp\?msg=quote["\']/', $html);
        $this->assertStringNotContainsString('whatsapp?msg=hire', $html);
        $this->assertStringNotContainsString('whatsapp?msg=need_caregiver', $html);
    }

    public function test_investors_and_emergency_use_context_keys(): void
    {
        $investors = $this->get(route('investors'))->assertOk()->getContent();
        $this->assertStringContainsString('whatsapp?msg=investor', $investors);
        $this->assertStringContainsString('whatsapp?msg=contact', $investors);

        $emergency = $this->get(route('legal.emergency'))->assertOk()->getContent();
        $this->assertStringContainsString('whatsapp?msg=urgent', $emergency);
        $this->assertStringContainsString('whatsapp?msg=contact', $emergency);
        $this->assertStringNotContainsString('whatsapp?msg=quote', $emergency);
    }

    public function test_legal_pages_wire_policy_specific_keys(): void
    {
        $terms = $this->get(route('legal.terms'))->assertOk()->getContent();
        $this->assertStringContainsString('whatsapp?msg=legal_terms', $terms);

        $cancellation = $this->get(route('legal.cancellation'))->assertOk()->getContent();
        $this->assertStringContainsString('whatsapp?msg=legal_cancellation', $cancellation);

        $payment = $this->get(route('legal.payment'))->assertOk()->getContent();
        $this->assertStringContainsString('whatsapp?msg=legal_payment', $payment);

        $caregiverTerms = $this->get(route('legal.caregiver-terms'))->assertOk()->getContent();
        $this->assertStringContainsString('whatsapp?msg=legal_caregiver_terms', $caregiverTerms);
        $this->assertStringNotContainsString('whatsapp?msg=caregiver"', $caregiverTerms);
    }

    public function test_cta_redirects_encode_contextual_utf8_text(): void
    {
        $cases = [
            'default' => 'cuidado domiciliar',
            'quote' => 'familiar',
            'caregiver' => 'Sou cuidador',
            'investor' => 'parceria ou investimento',
            'urgent' => 'ajuda imediata',
            'contact' => 'entrar em contato',
            'legal_privacy' => 'Política de Privacidade',
        ];

        foreach ($cases as $key => $snippet) {
            $location = urldecode((string) $this->get(route('whatsapp.cta', ['msg' => $key]))->headers->get('Location'));
            $this->assertStringContainsString('https://wa.me/5589999771471?text=', $location);
            $this->assertStringContainsString($snippet, $location);
        }
    }
}
