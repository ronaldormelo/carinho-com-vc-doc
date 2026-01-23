<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Lista faturas com filtros.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['status', 'items']);

        // Filtros
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('contract_id')) {
            $query->where('contract_id', $request->contract_id);
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('status')) {
            $query->whereHas('status', fn ($q) => $q->where('code', $request->status));
        }

        if ($request->has('overdue') && $request->overdue) {
            $query->overdue();
        }

        if ($request->has('due_soon')) {
            $query->dueSoon((int) $request->due_soon);
        }

        if ($request->has('period_start') && $request->has('period_end')) {
            $query->forPeriod($request->period_start, $request->period_end);
        }

        // Ordenação
        $sortField = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        // Paginação
        $perPage = min($request->get('per_page', 15), 100);
        $invoices = $query->paginate($perPage);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Cria nova fatura.
     */
    public function store(InvoiceRequest $request)
    {
        try {
            $invoice = $this->invoiceService->createInvoice($request->validated());

            return $this->createdResponse(
                new InvoiceResource($invoice),
                'Fatura criada com sucesso'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Exibe fatura específica.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['status', 'items.serviceType', 'payments.method', 'payments.status', 'fiscalDocument']);

        return new InvoiceResource($invoice);
    }

    /**
     * Adiciona item à fatura.
     */
    public function addItem(Request $request, Invoice $invoice)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'nullable|numeric|min:0',
            'service_type_id' => 'nullable|integer',
            'service_date' => 'nullable|date',
            'caregiver_id' => 'nullable|integer',
        ]);

        try {
            $item = $this->invoiceService->addItem($invoice, $request->all());

            return $this->successResponse([
                'item' => $item,
                'invoice_total' => $invoice->fresh()->total_amount,
            ], 'Item adicionado');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Remove item da fatura.
     */
    public function removeItem(Invoice $invoice, int $itemId)
    {
        try {
            $this->invoiceService->removeItem($invoice, $itemId);

            return $this->successResponse([
                'invoice_total' => $invoice->fresh()->total_amount,
            ], 'Item removido');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Aplica desconto à fatura.
     */
    public function applyDiscount(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $invoice = $this->invoiceService->applyDiscount(
                $invoice,
                $request->amount,
                $request->reason
            );

            return $this->successResponse(
                new InvoiceResource($invoice),
                'Desconto aplicado'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Cancela fatura.
     */
    public function cancel(Request $request, Invoice $invoice)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'service_date' => 'nullable|date',
        ]);

        try {
            $serviceDate = $request->service_date 
                ? Carbon::parse($request->service_date) 
                : null;

            $result = $this->invoiceService->cancelInvoice(
                $invoice,
                $request->reason,
                $serviceDate
            );

            return $this->successResponse([
                'invoice' => new InvoiceResource($invoice->fresh()),
                'cancellation' => $result,
            ], 'Fatura cancelada');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Obtém resumo financeiro de um cliente.
     */
    public function clientSummary(int $clientId)
    {
        $summary = $this->invoiceService->getClientSummary($clientId);
        return $this->successResponse($summary);
    }

    /**
     * Lista faturas vencidas.
     */
    public function overdue(Request $request)
    {
        $invoices = Invoice::with(['status'])
            ->overdue()
            ->orderBy('due_date', 'asc')
            ->paginate($request->get('per_page', 15));

        return InvoiceResource::collection($invoices);
    }

    /**
     * Lista faturas que vencem em breve.
     */
    public function dueSoon(Request $request)
    {
        $days = $request->get('days', 3);
        
        $invoices = Invoice::with(['status'])
            ->dueSoon($days)
            ->orderBy('due_date', 'asc')
            ->get();

        return InvoiceResource::collection($invoices);
    }
}
