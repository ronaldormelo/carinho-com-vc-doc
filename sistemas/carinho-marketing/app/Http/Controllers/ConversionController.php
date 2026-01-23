<?php

namespace App\Http\Controllers;

use App\Services\ConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversionController extends Controller
{
    public function __construct(
        private ConversionService $service
    ) {}

    /**
     * Registra conversao de lead.
     */
    public function registerLead(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'utm_source' => 'nullable|string',
            'utm_medium' => 'nullable|string',
            'utm_campaign' => 'nullable|string',
            'utm_content' => 'nullable|string',
            'utm_term' => 'nullable|string',
            'referrer' => 'nullable|string',
            'url' => 'nullable|string|url',
        ]);

        try {
            $leadData = $request->only(['id', 'name', 'email', 'phone', 'url', 'referrer']);
            $leadData['ip'] = $request->ip();
            $leadData['user_agent'] = $request->userAgent();
            $leadData['fbc'] = $request->cookie('_fbc');
            $leadData['fbp'] = $request->cookie('_fbp');
            $leadData['gclid'] = $request->input('gclid');
            $leadData['ga_client_id'] = $request->input('ga_client_id');

            $sourceData = $request->only([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'
            ]);

            $results = $this->service->registerLeadConversion($leadData, $sourceData);

            return $this->created($results, 'Conversao de lead registrada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Registra conversao de contato (WhatsApp).
     */
    public function registerContact(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'name' => 'nullable|string|max:255',
            'utm_source' => 'nullable|string',
            'utm_medium' => 'nullable|string',
            'utm_campaign' => 'nullable|string',
        ]);

        try {
            $contactData = $request->only(['phone', 'name', 'url']);
            $contactData['ip'] = $request->ip();
            $contactData['user_agent'] = $request->userAgent();
            $contactData['fbc'] = $request->cookie('_fbc');
            $contactData['fbp'] = $request->cookie('_fbp');

            $sourceData = $request->only([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'
            ]);

            $results = $this->service->registerContactConversion($contactData, $sourceData);

            return $this->created($results, 'Conversao de contato registrada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Registra conversao de cadastro.
     */
    public function registerRegistration(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'first_name' => 'nullable|string|max:128',
            'lead_id' => 'nullable|string',
        ]);

        try {
            $userData = $request->only(['email', 'phone', 'first_name', 'lead_id', 'url']);
            $userData['ip'] = $request->ip();
            $userData['user_agent'] = $request->userAgent();
            $userData['gclid'] = $request->input('gclid');

            $sourceData = $request->only([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'
            ]);

            $results = $this->service->registerRegistrationConversion($userData, $sourceData);

            return $this->created($results, 'Conversao de cadastro registrada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Lista eventos de conversao.
     */
    public function listEvents(): JsonResponse
    {
        $events = $this->service->listEvents();

        return $this->success($events, 'Eventos carregados');
    }

    /**
     * Cria evento de conversao.
     */
    public function createEvent(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'event_key' => 'required|string|max:128',
            'target_url' => 'required|string|url|max:512',
        ]);

        try {
            $event = $this->service->createEvent($request->all());

            return $this->created($event->toArray(), 'Evento criado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Estatisticas de conversao.
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $stats = $this->service->getConversionStats(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->success($stats, 'Estatisticas carregadas');
    }
}
