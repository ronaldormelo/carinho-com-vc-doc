<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Controller para paginas de documentos legais.
 */
class LegalController extends Controller
{
    /**
     * Politica de Privacidade.
     */
    public function privacy(): View
    {
        return view('legal.privacy', [
            'seo' => [
                'title' => 'Politica de Privacidade' . config('branding.seo.title_suffix'),
                'description' => 'Saiba como a Carinho com Voce coleta, usa e protege seus dados pessoais em conformidade com a LGPD.',
            ],
        ]);
    }

    /**
     * Termos de Uso.
     */
    public function terms(): View
    {
        return view('legal.terms', [
            'seo' => [
                'title' => 'Termos de Uso' . config('branding.seo.title_suffix'),
                'description' => 'Leia os termos e condicoes de uso dos servicos da Carinho com Voce.',
            ],
        ]);
    }

    /**
     * Politica de Cancelamento.
     */
    public function cancellation(): View
    {
        $policy = config('site.cancellation_policy');

        return view('legal.cancellation', [
            'policy' => $policy,
            'seo' => [
                'title' => 'Politica de Cancelamento' . config('branding.seo.title_suffix'),
                'description' => 'Entenda nossa politica de cancelamento, prazos e condicoes de reembolso.',
            ],
        ]);
    }

    /**
     * Politica de Pagamento.
     */
    public function payment(): View
    {
        $paymentPolicy = config('site.payment_policy');
        $payoutPolicy = config('site.payout_policy');
        $commission = config('site.caregiver_commission');

        return view('legal.payment', [
            'paymentPolicy' => $paymentPolicy,
            'payoutPolicy' => $payoutPolicy,
            'commission' => $commission,
            'seo' => [
                'title' => 'Politica de Pagamento e Comissoes' . config('branding.seo.title_suffix'),
                'description' => 'Informacoes sobre prazos de pagamento, formas aceitas e comissoes dos cuidadores.',
            ],
        ]);
    }

    /**
     * Politica de Emergencias.
     */
    public function emergency(): View
    {
        $policy = config('site.emergency_policy');
        $sla = config('site.sla');

        return view('legal.emergency', [
            'policy' => $policy,
            'sla' => $sla,
            'seo' => [
                'title' => 'Politica de Emergencias' . config('branding.seo.title_suffix'),
                'description' => 'Saiba como agir em situacoes de emergencia e como a Carinho com Voce oferece suporte.',
            ],
        ]);
    }

    /**
     * Termos para Cuidadores.
     */
    public function caregiverTerms(): View
    {
        $commission = config('site.caregiver_commission');
        $payoutPolicy = config('site.payout_policy');

        return view('legal.caregiver-terms', [
            'commission' => $commission,
            'payoutPolicy' => $payoutPolicy,
            'seo' => [
                'title' => 'Termos para Cuidadores' . config('branding.seo.title_suffix'),
                'description' => 'Termos e condicoes para cuidadores parceiros da Carinho com Voce.',
            ],
        ]);
    }
}
