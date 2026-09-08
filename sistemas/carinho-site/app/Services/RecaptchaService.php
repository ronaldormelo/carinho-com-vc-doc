<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Servico de validacao reCAPTCHA v3.
 *
 * Contrato: quando a integracao nao esta completa, ou quando o Google
 * estiver lento/indisponivel, nao impedir a persistencia do envio local
 * (docs/nao-funcionais.md). Tokens vazios, respostas invalidas e score
 * baixo continuam sendo recusados.
 */
class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** Erros de operador: secret ausente/invalido. Bloquear todos os leads nao ajuda. */
    private const CONFIG_ERROR_CODES = [
        'missing-input-secret',
        'invalid-input-secret',
    ];

    /**
     * reCAPTCHA so e exigido quando habilitado e com as duas chaves.
     */
    public function isConfigured(): bool
    {
        return (bool) config('integrations.recaptcha.enabled')
            && filled(config('integrations.recaptcha.site_key'))
            && filled(config('integrations.recaptcha.secret_key'));
    }

    /**
     * Verifica token do reCAPTCHA.
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (!$this->isConfigured()) {
            if (config('integrations.recaptcha.enabled')) {
                Log::warning('reCAPTCHA habilitado mas site_key/secret_key ausentes; validacao ignorada');
            }

            return true;
        }

        if (!is_string($token) || $token === '') {
            Log::warning('reCAPTCHA token vazio');

            return false;
        }

        try {
            $payload = [
                'secret' => config('integrations.recaptcha.secret_key'),
                'response' => $token,
            ];

            if (is_string($ip) && $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                $payload['remoteip'] = $ip;
            }

            $timeout = (int) config('integrations.recaptcha.timeout', 5);

            $response = Http::asForm()
                ->connectTimeout(3)
                ->timeout($timeout)
                ->post(self::VERIFY_URL, $payload);

            if (!$response->successful()) {
                Log::error('reCAPTCHA HTTP nao-sucesso; envio aceito para nao bloquear lead', [
                    'status' => $response->status(),
                ]);

                return true;
            }

            $data = $response->json();

            if (!is_array($data)) {
                Log::error('reCAPTCHA resposta invalida; envio aceito para nao bloquear lead');

                return true;
            }

            $errorCodes = $data['error-codes'] ?? [];

            if (array_intersect(self::CONFIG_ERROR_CODES, $errorCodes) !== []) {
                Log::error('reCAPTCHA secret invalido ou ausente; envio aceito para nao bloquear lead', [
                    'error-codes' => $errorCodes,
                ]);

                return true;
            }

            if (empty($data['success'])) {
                Log::warning('reCAPTCHA validacao falhou', [
                    'error-codes' => $errorCodes,
                ]);

                return false;
            }

            $minScore = (float) config('integrations.recaptcha.min_score', 0.5);

            if (array_key_exists('score', $data) && (float) $data['score'] < $minScore) {
                Log::warning('reCAPTCHA score abaixo do minimo', [
                    'score' => $data['score'],
                    'min_score' => $minScore,
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Erro ao verificar reCAPTCHA; envio aceito para nao bloquear lead', [
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }
}
