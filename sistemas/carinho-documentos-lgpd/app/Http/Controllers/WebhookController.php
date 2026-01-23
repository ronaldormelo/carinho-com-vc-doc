<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Webhook de assinatura (providers externos).
     */
    public function signature(Request $request): JsonResponse
    {
        Log::info('Signature webhook received', [
            'payload' => $request->all(),
        ]);

        // Implementacao para webhooks de provedores de assinatura digital
        // Exemplo: DocuSign, ClickSign, etc.

        return $this->success(null, 'Webhook recebido');
    }

    /**
     * Webhook de storage (eventos S3).
     */
    public function storage(Request $request): JsonResponse
    {
        Log::info('Storage webhook received', [
            'payload' => $request->all(),
        ]);

        // Implementacao para eventos do S3
        // Exemplo: ObjectCreated, ObjectRemoved, etc.

        return $this->success(null, 'Webhook recebido');
    }
}
