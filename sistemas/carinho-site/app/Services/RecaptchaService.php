<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servico de validacao reCAPTCHA v3.
 */
class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Verifica token do reCAPTCHA.
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (!config('integrations.recaptcha.enabled')) {
            return true;
        }

        if (empty($token)) {
            Log::warning('reCAPTCHA token vazio');
            return false;
        }

        $secretKey = config('integrations.recaptcha.secret_key');

        if (empty($secretKey)) {
            Log::warning('reCAPTCHA secret key nao configurada');
            return true; // Aceita se nao configurado
        }

        try {
            $response = Http::asForm()->post(self::VERIFY_URL, [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            $data = $response->json();

            if (!$data['success']) {
                Log::warning('reCAPTCHA validacao falhou', [
                    'error-codes' => $data['error-codes'] ?? [],
                ]);
                return false;
            }

            $minScore = config('integrations.recaptcha.min_score', 0.5);

            if (($data['score'] ?? 0) < $minScore) {
                Log::warning('reCAPTCHA score abaixo do minimo', [
                    'score' => $data['score'] ?? 0,
                    'min_score' => $minScore,
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao verificar reCAPTCHA', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
