<?php

namespace Tests\Unit;

use App\Services\RecaptchaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaServiceTest extends TestCase
{
    private RecaptchaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RecaptchaService::class);
    }

    public function test_aceita_quando_habilitado_sem_chaves_mesmo_com_token_vazio(): void
    {
        config([
            'integrations.recaptcha.enabled' => true,
            'integrations.recaptcha.site_key' => '',
            'integrations.recaptcha.secret_key' => '',
        ]);

        $this->assertFalse($this->service->isConfigured());
        $this->assertTrue($this->service->verify(null));
        $this->assertTrue($this->service->verify(''));
    }

    public function test_recusa_token_vazio_quando_configurado(): void
    {
        $this->configureRecaptcha();

        $this->assertTrue($this->service->isConfigured());
        $this->assertFalse($this->service->verify(null));
        $this->assertFalse($this->service->verify(''));
    }

    public function test_aceita_score_acima_do_minimo(): void
    {
        $this->configureRecaptcha();

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'submit_investor',
            ]),
        ]);

        $this->assertTrue($this->service->verify('token-valido', '203.0.113.10'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
                && $request['response'] === 'token-valido'
                && $request['remoteip'] === '203.0.113.10';
        });
    }

    public function test_recusa_score_abaixo_do_minimo(): void
    {
        $this->configureRecaptcha();

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.1,
            ]),
        ]);

        $this->assertFalse($this->service->verify('token-valido'));
    }

    public function test_recusa_resposta_invalida_do_google(): void
    {
        $this->configureRecaptcha();

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);

        $this->assertFalse($this->service->verify('token-falso'));
    }

    public function test_aceita_quando_secret_do_google_e_invalido(): void
    {
        $this->configureRecaptcha();

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-secret'],
            ]),
        ]);

        $this->assertTrue($this->service->verify('token-qualquer'));
    }

    public function test_aceita_quando_google_esta_indisponivel(): void
    {
        $this->configureRecaptcha();

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response('bad gateway', 502),
        ]);

        $this->assertTrue($this->service->verify('token-valido'));
    }

    public function test_aceita_quando_google_estoura_timeout(): void
    {
        $this->configureRecaptcha();

        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->assertTrue($this->service->verify('token-valido'));
    }

    public function test_nao_envia_remoteip_invalido(): void
    {
        $this->configureRecaptcha();

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
            ]),
        ]);

        $this->assertTrue($this->service->verify('token-valido', 'not-an-ip'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
                && ! isset($request['remoteip']);
        });
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
}
