<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Lista pagamentos com filtros.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['invoice', 'method', 'status']);

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('method_id')) {
            $query->where('method_id', $request->method_id);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->forPeriod($request->date_from, $request->date_to);
        }

        $sortField = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $perPage = min($request->get('per_page', 15), 100);
        $payments = $query->paginate($perPage);

        return PaymentResource::collection($payments);
    }

    /**
     * Cria pagamento para uma fatura.
     */
    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'method' => 'required|string|in:pix,boleto,card',
            'metadata' => 'nullable|array',
        ]);

        try {
            $invoice = Invoice::findOrFail($request->invoice_id);
            $payment = $this->paymentService->createPayment(
                $invoice,
                $request->method,
                $request->metadata
            );

            return $this->createdResponse(
                new PaymentResource($payment->load(['method', 'status'])),
                'Pagamento criado'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Exibe pagamento específico.
     */
    public function show(Payment $payment)
    {
        $payment->load(['invoice.items', 'method', 'status']);
        return new PaymentResource($payment);
    }

    /**
     * Gera link de pagamento.
     */
    public function generateLink(Request $request, Invoice $invoice)
    {
        $request->validate([
            'method' => 'nullable|string|in:pix,boleto,card',
        ]);

        try {
            $link = $this->paymentService->generatePaymentLink(
                $invoice,
                $request->get('method', 'pix')
            );

            return $this->successResponse($link, 'Link de pagamento gerado');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Processa reembolso.
     */
    public function refund(Request $request, Payment $payment)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $payment = $this->paymentService->refund(
                $payment,
                $request->amount,
                $request->reason
            );

            return $this->successResponse(
                new PaymentResource($payment),
                'Reembolso processado'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Consulta status de pagamento no Stripe.
     */
    public function checkStatus(Payment $payment)
    {
        if (!$payment->stripe_payment_intent_id) {
            return $this->errorResponse('Pagamento sem ID do Stripe', 422);
        }

        try {
            $status = $this->paymentService->getPaymentStatus(
                $payment->stripe_payment_intent_id
            );

            return $this->successResponse([
                'local_status' => $payment->status->code,
                'stripe_status' => $status['data']['status'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Lista pagamentos de uma fatura.
     */
    public function invoicePayments(int $invoiceId)
    {
        $payments = $this->paymentService->getInvoicePayments($invoiceId);
        return $this->successResponse($payments);
    }
}
