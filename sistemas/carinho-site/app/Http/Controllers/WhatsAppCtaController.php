<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Redireciona CTAs do site para o WhatsApp com mensagem da acao clicada.
 */
class WhatsAppCtaController extends Controller
{
    public function __invoke(Request $request, WhatsAppService $whatsApp): RedirectResponse
    {
        $utm = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $param) {
            if ($request->session()->has($param)) {
                $utm[$param] = (string) $request->session()->get($param);
            }
        }

        return redirect()->away($whatsApp->buildCtaRedirectUrl(
            $request->query('msg'),
            $utm
        ));
    }
}
