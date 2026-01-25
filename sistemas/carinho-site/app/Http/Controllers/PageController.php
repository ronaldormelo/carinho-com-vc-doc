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
                'description' => 'Conheca a Carinho com Voce. Somos especializados em conectar familias a cuidadores qualificados de forma rapida, segura e humanizada.',
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
                'title' => 'Nossos Servicos' . config('branding.seo.title_suffix'),
                'description' => 'Oferecemos servicos de cuidadores por hora, diarios ou mensais. Encontre o modelo ideal para sua necessidade.',
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
                'description' => 'Precisa de um cuidador qualificado? Contratacao rapida e sem complicacao. Resposta em ate 5 minutos!',
            ],
        ]);
    }

    /**
     * Pagina para Cuidadores.
     */
    public function forCaregivers(): View
    {
        $commissions = config('site.caregiver_commission');

        return view('pages.caregivers', [
            'commissions' => $commissions,
            'seo' => [
                'title' => 'Para Cuidadores - Trabalhe Conosco' . config('branding.seo.title_suffix'),
                'description' => 'Seja um cuidador parceiro da Carinho com Voce. Mais oportunidades, recorrencia e suporte profissional.',
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
                'description' => 'Entre em contato conosco pelo WhatsApp ou preencha o formulario. Respondemos em ate 5 minutos durante o horario comercial.',
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
                'description' => 'Encontre respostas para as duvidas mais comuns sobre nossos servicos de cuidadores domiciliares.',
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
                'description' => 'Veja como e facil contratar um cuidador pela Carinho com Voce. Processo 100% digital em poucos minutos.',
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
