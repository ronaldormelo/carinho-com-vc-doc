<?php

namespace App\Http\Controllers;

use App\Models\FaqCategory;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para paginas institucionais do site.
 */
class PageController extends Controller
{
    /**
     * Pagina inicial (Home).
     */
    public function home(): View
    {
        $testimonials = Testimonial::active()
            ->featured()
            ->orderByDesc('rating')
            ->limit(6)
            ->get();

        return view('pages.home', [
            'testimonials' => $testimonials,
            'seo' => [
                'title' => config('branding.seo.default_title'),
                'description' => config('branding.seo.default_description'),
                'keywords' => config('branding.seo.default_keywords'),
            ],
        ]);
    }

    /**
     * Pagina "Quem Somos".
     */
    public function about(): View
    {
        return view('pages.about', [
            'seo' => [
                'title' => 'Quem Somos' . config('branding.seo.title_suffix'),
                'description' => 'Conheça a Carinho com Você. Somos especializados em conectar famílias a cuidadores qualificados de forma rápida, segura e humanizada.',
            ],
        ]);
    }

    /**
     * Pagina de Servicos.
     */
    public function services(): View
    {
        $serviceTypes = config('site.service_types');

        return view('pages.services', [
            'serviceTypes' => $serviceTypes,
            'seo' => [
                'title' => 'Nossos Serviços' . config('branding.seo.title_suffix'),
                'description' => 'Oferecemos serviços de cuidadores por hora, diários ou mensais. Encontre o modelo ideal para sua necessidade.',
            ],
        ]);
    }

    /**
     * Pagina para Clientes.
     */
    public function forClients(): View
    {
        $serviceTypes = config('site.service_types');
        $urgencyLevels = config('site.urgency_levels');

        return view('pages.clients', [
            'serviceTypes' => $serviceTypes,
            'urgencyLevels' => $urgencyLevels,
            'seo' => [
                'title' => 'Para Clientes - Contrate um Cuidador' . config('branding.seo.title_suffix'),
                'description' => 'Precisa de um cuidador qualificado? Contratação rápida e sem complicação. Resposta em até 5 minutos!',
            ],
        ]);
    }

    /**
     * Pagina para Cuidadores.
     */
    public function forCaregivers(): View
    {
        $commissions = config('site.caregiver_commission');
        $payoutPolicy = config('site.payout_policy');

        return view('pages.caregivers', [
            'commissions' => $commissions,
            'payoutPolicy' => $payoutPolicy,
            'seo' => [
                'title' => 'Para Cuidadores - Trabalhe Conosco' . config('branding.seo.title_suffix'),
                'description' => 'Seja um cuidador parceiro da Carinho com Você. Mais oportunidades, recorrência e suporte profissional.',
            ],
        ]);
    }

    /**
     * Pagina de Contato.
     */
    public function contact(): View
    {
        return view('pages.contact', [
            'seo' => [
                'title' => 'Contato' . config('branding.seo.title_suffix'),
                'description' => 'Entre em contato conosco pelo WhatsApp ou preencha o formulário. Respondemos em até 5 minutos durante o horário comercial.',
            ],
        ]);
    }

    /**
     * Pagina de FAQ.
     */
    public function faq(): View
    {
        $categories = FaqCategory::active()
            ->with(['items' => fn ($q) => $q->active()])
            ->get();

        return view('pages.faq', [
            'categories' => $categories,
            'seo' => [
                'title' => 'Perguntas Frequentes' . config('branding.seo.title_suffix'),
                'description' => 'Encontre respostas para as dúvidas mais comuns sobre nossos serviços de cuidadores domiciliares.',
            ],
        ]);
    }

    /**
     * Pagina de Como Funciona.
     */
    public function howItWorks(): View
    {
        return view('pages.how-it-works', [
            'seo' => [
                'title' => 'Como Funciona' . config('branding.seo.title_suffix'),
                'description' => 'Veja como é fácil contratar um cuidador pela Carinho com Você. Processo 100% digital em poucos minutos.',
            ],
        ]);
    }

    /**
     * Pagina para Investidores.
     */
    public function investors(): View
    {
        return view('pages.investors', [
            'seo' => [
                'title' => 'Investidores - Plano de Negócios' . config('branding.seo.title_suffix'),
                'description' => 'Conheça o plano de negócios da Carinho com Você. Plataforma digital de cuidadores domiciliares com modelo escalável e mercado em expansão.',
            ],
        ]);
    }
}
