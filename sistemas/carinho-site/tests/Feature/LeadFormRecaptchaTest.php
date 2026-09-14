<?php

namespace Tests\Feature;

use App\Jobs\SendLeadNotification;
use App\Jobs\SyncLeadToCrm;
use App\Models\Domain\DomainFormTarget;
use App\Models\FormSubmission;
use App\Models\LeadForm;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeadFormRecaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            ThrottleRequests::class,
        ]);

        Queue::fake();

        LeadForm::create([
            'name' => 'Formulário de Cliente',
            'target_type_id' => DomainFormTarget::CLIENTE,
            'fields_json' => [],
            'active' => true,
        ]);

        LeadForm::create([
            'name' => 'Formulário de Cuidador',
            'target_type_id' => DomainFormTarget::CUIDADOR,
            'fields_json' => [],
            'active' => true,
        ]);
    }

    public function test_investidor_envia_quando_recaptcha_nao_esta_configurado(): void
    {
        config([
            'integrations.recaptcha.enabled' => true,
            'integrations.recaptcha.site_key' => '',
            'integrations.recaptcha.secret_key' => '',
        ]);

        $response = $this->postJson(route('lead.investor.submit'), $this->investorPayload());

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('form_submissions', 1);
        Queue::assertPushed(SyncLeadToCrm::class);
        Queue::assertPushed(SendLeadNotification::class);
    }

    public function test_cuidador_envia_quando_recaptcha_nao_esta_configurado(): void
    {
        config([
            'integrations.recaptcha.enabled' => true,
            'integrations.recaptcha.site_key' => '',
            'integrations.recaptcha.secret_key' => '',
        ]);

        $response = $this->postJson(route('lead.caregiver.submit'), $this->caregiverPayload());

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('form_submissions', 1);
        $this->assertSame('Maria Cuidadora', FormSubmission::query()->value('name'));
    }

    public function test_investidor_recusa_token_vazio_quando_recaptcha_configurado(): void
    {
        $this->configureRecaptcha();

        $response = $this->postJson(route('lead.investor.submit'), $this->investorPayload([
            'recaptcha_token' => '',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validação de segurança falhou. Por favor, tente novamente.');

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_cuidador_recusa_token_vazio_quando_recaptcha_configurado(): void
    {
        $this->configureRecaptcha();

        $response = $this->postJson(route('lead.caregiver.submit'), $this->caregiverPayload([
            'recaptcha_token' => '',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validação de segurança falhou. Por favor, tente novamente.');

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_investidor_envia_quando_google_confirma_token(): void
    {
        $this->configureRecaptcha();

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.8,
                'action' => 'submit_investor',
            ]),
        ]);

        $response = $this->postJson(route('lead.investor.submit'), $this->investorPayload([
            'recaptcha_token' => 'token-ok',
        ]));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseCount('form_submissions', 1);
    }

    public function test_cuidador_envia_quando_google_confirma_token(): void
    {
        $this->configureRecaptcha();

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.8,
                'action' => 'submit_caregiver',
            ]),
        ]);

        $response = $this->postJson(route('lead.caregiver.submit'), $this->caregiverPayload([
            'recaptcha_token' => 'token-ok',
        ]));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseCount('form_submissions', 1);
    }

    public function test_paginas_publicas_carregam_helper_recaptcha_quando_configurado(): void
    {
        $this->configureRecaptcha();

        $this->get(route('investors'))
            ->assertOk()
            ->assertSee('carinhoGetRecaptchaToken', false)
            ->assertSee('data-cfasync="false"', false)
            ->assertSee('submit_investor', false);

        $this->get(route('caregivers'))
            ->assertOk()
            ->assertSee('carinhoGetRecaptchaToken', false)
            ->assertSee('submit_caregiver', false);
    }

    private function configureRecaptcha(): void
    {
        config([
            'integrations.recaptcha.enabled' => true,
            'integrations.recaptcha.site_key' => 'site-key',
            'integrations.recaptcha.secret_key' => 'secret-key',
            'integrations.recaptcha.min_score' => 0.5,
            'integrations.recaptcha.timeout' => 5,
        ]);
    }

    private function investorPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Investidora',
            'email' => 'ana@fundo.com',
            'phone' => '11999998888',
            'company' => 'Fundo Exemplo',
            'interest' => 'investimento',
            'message' => 'Quero conhecer a tese.',
            'consent' => '1',
        ], $overrides);
    }

    private function caregiverPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Cuidadora',
            'phone' => '11988887777',
            'email' => 'maria@email.com',
            'city' => 'São Paulo',
            'experience_years' => 5,
            'consent' => '1',
        ], $overrides);
    }
}
