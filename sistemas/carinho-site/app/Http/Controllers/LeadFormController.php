<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientLeadRequest;
use App\Http\Requests\CaregiverLeadRequest;
use App\Jobs\SyncLeadToCrm;
use App\Jobs\SendLeadNotification;
use App\Models\FormSubmission;
use App\Models\LeadForm;
use App\Models\UtmCampaign;
use App\Models\Domain\DomainFormTarget;
use App\Services\RecaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para formularios de captacao de leads.
 */
class LeadFormController extends Controller
{
    public function __construct(
        private RecaptchaService $recaptcha
    ) {}

    /**
     * Submete formulario de lead cliente.
     */
    public function submitClientLead(ClientLeadRequest $request): JsonResponse
    {
        // Valida reCAPTCHA
        if (config('integrations.recaptcha.enabled')) {
            $recaptchaValid = $this->recaptcha->verify(
                $request->input('recaptcha_token'),
                $request->ip()
            );

            if (!$recaptchaValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validacao de seguranca falhou. Por favor, tente novamente.',
                ], 422);
            }
        }

        // Encontra ou cria UTM
        $utmParams = UtmCampaign::extractFromRequest($request);
        $utm = UtmCampaign::findOrCreateFromParams($utmParams);

        // Obtem formulario de cliente
        $form = LeadForm::where('target_type_id', DomainFormTarget::CLIENTE)
            ->active()
            ->first();

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'Formulario nao configurado.',
            ], 500);
        }

        // Cria submissao
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'utm_id' => $utm?->id,
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'city' => $request->input('city'),
            'urgency_id' => $request->input('urgency_id'),
            'service_type_id' => $request->input('service_type_id'),
            'consent_at' => $request->boolean('consent') ? now() : null,
            'payload_json' => [
                'neighborhood' => $request->input('neighborhood'),
                'patient_name' => $request->input('patient_name'),
                'patient_condition' => $request->input('patient_condition'),
                'preferred_schedule' => $request->input('preferred_schedule'),
                'message' => $request->input('message'),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Envia para fila de sincronizacao com CRM
        SyncLeadToCrm::dispatch($submission);

        // Envia notificacao de novo lead
        SendLeadNotification::dispatch($submission);

        return response()->json([
            'success' => true,
            'message' => 'Formulario enviado com sucesso! Entraremos em contato em breve.',
            'whatsapp_url' => $this->generateWhatsAppUrl($submission),
        ]);
    }

    /**
     * Submete formulario de lead cuidador.
     */
    public function submitCaregiverLead(CaregiverLeadRequest $request): JsonResponse
    {
        // Valida reCAPTCHA
        if (config('integrations.recaptcha.enabled')) {
            $recaptchaValid = $this->recaptcha->verify(
                $request->input('recaptcha_token'),
                $request->ip()
            );

            if (!$recaptchaValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validacao de seguranca falhou. Por favor, tente novamente.',
                ], 422);
            }
        }

        // Encontra ou cria UTM
        $utmParams = UtmCampaign::extractFromRequest($request);
        $utm = UtmCampaign::findOrCreateFromParams($utmParams);

        // Obtem formulario de cuidador
        $form = LeadForm::where('target_type_id', DomainFormTarget::CUIDADOR)
            ->active()
            ->first();

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'Formulario nao configurado.',
            ], 500);
        }

        // Cria submissao
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'utm_id' => $utm?->id,
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'city' => $request->input('city'),
            'urgency_id' => $request->input('urgency_id', 3), // Sem data definida
            'service_type_id' => $request->input('service_type_id', 1), // Horista
            'consent_at' => $request->boolean('consent') ? now() : null,
            'payload_json' => [
                'experience_years' => $request->input('experience_years'),
                'has_course' => $request->boolean('has_course'),
                'specialties' => $request->input('specialties', []),
                'availability' => $request->input('availability'),
                'neighborhoods' => $request->input('neighborhoods', []),
                'message' => $request->input('message'),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Envia para fila de sincronizacao com CRM
        SyncLeadToCrm::dispatch($submission, 'cuidador');

        // Envia notificacao
        SendLeadNotification::dispatch($submission, 'cuidador');

        return response()->json([
            'success' => true,
            'message' => 'Cadastro enviado com sucesso! Analisaremos seu perfil e entraremos em contato.',
            'whatsapp_url' => $this->generateCaregiverWhatsAppUrl($submission),
        ]);
    }

    /**
     * Gera URL do WhatsApp para cliente.
     */
    private function generateWhatsAppUrl(FormSubmission $submission): string
    {
        $phone = config('branding.contact.whatsapp');
        $message = config('branding.whatsapp_messages.client');

        // Adiciona UTM se existir
        $utmSuffix = '';
        if ($submission->utm) {
            $utmSuffix = "?utm_source={$submission->utm->source}&utm_medium={$submission->utm->medium}&utm_campaign={$submission->utm->campaign}";
        }

        return "https://wa.me/{$phone}?text=" . urlencode($message) . $utmSuffix;
    }

    /**
     * Gera URL do WhatsApp para cuidador.
     */
    private function generateCaregiverWhatsAppUrl(FormSubmission $submission): string
    {
        $phone = config('branding.contact.whatsapp');
        $message = config('branding.whatsapp_messages.caregiver');

        return "https://wa.me/{$phone}?text=" . urlencode($message);
    }
}
