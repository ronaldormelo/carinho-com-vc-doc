<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Models\SitePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Controller para webhooks de sistemas internos.
 */
class WebhookController extends Controller
{
    /**
     * Recebe evento de atualizacao do CRM.
     */
    public function crmUpdate(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $data = $request->input('data');

        switch ($event) {
            case 'lead_updated':
                // Processa atualizacao de lead se necessario
                break;

            case 'client_created':
                // Cliente foi criado a partir do lead
                break;

            default:
                // Evento desconhecido
                break;
        }

        return response()->json(['status' => 'received']);
    }

    /**
     * Limpa cache de paginas.
     */
    public function clearPageCache(Request $request): JsonResponse
    {
        $slug = $request->input('slug');

        if ($slug) {
            Cache::forget("page_{$slug}");
        } else {
            // Limpa cache de todas as paginas
            $pages = SitePage::all();
            foreach ($pages as $page) {
                Cache::forget("page_{$page->slug}");
            }
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Cache limpo com sucesso',
        ]);
    }

    /**
     * Limpa cache de documentos legais.
     */
    public function clearLegalCache(Request $request): JsonResponse
    {
        $typeId = $request->input('type_id');

        if ($typeId) {
            Cache::forget("legal_doc_{$typeId}");
        } else {
            // Limpa cache de todos os documentos legais
            for ($i = 1; $i <= 6; $i++) {
                Cache::forget("legal_doc_{$i}");
            }
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Cache limpo com sucesso',
        ]);
    }
}
