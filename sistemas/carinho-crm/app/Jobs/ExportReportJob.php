<?php

namespace App\Jobs;

use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'crm-exports';
    public int $timeout = 300; // 5 minutos
    public int $tries = 3;

    public function __construct(
        protected string $reportType,
        protected string $format,
        protected ?string $startDate,
        protected ?string $endDate,
        protected int $userId
    ) {}

    public function handle(ReportService $reportService): void
    {
        Log::info("Iniciando exportação de relatório", [
            'type' => $this->reportType,
            'format' => $this->format,
            'user_id' => $this->userId,
        ]);

        try {
            // Gera dados do relatório
            $data = match($this->reportType) {
                'leads' => $this->getLeadsData($reportService),
                'clients' => $this->getClientsData($reportService),
                'deals' => $this->getDealsData($reportService),
                'contracts' => $this->getContractsData($reportService),
                default => throw new \InvalidArgumentException("Tipo de relatório inválido: {$this->reportType}"),
            };

            // Gera arquivo
            $filename = $this->generateFilename();
            $filepath = "exports/{$this->userId}/{$filename}";

            // Salva arquivo (simplificado - em produção usar Maatwebsite/Excel)
            if ($this->format === 'csv') {
                $this->exportCsv($data, $filepath);
            } else {
                // Para xlsx e pdf, usar bibliotecas específicas
                $this->exportCsv($data, str_replace(".{$this->format}", '.csv', $filepath));
            }

            Log::info("Exportação concluída", [
                'filepath' => $filepath,
                'user_id' => $this->userId,
            ]);

            // TODO: Enviar notificação por e-mail com link para download

        } catch (\Exception $e) {
            Log::error("Erro na exportação de relatório", [
                'error' => $e->getMessage(),
                'type' => $this->reportType,
                'user_id' => $this->userId,
            ]);
            throw $e;
        }
    }

    protected function getLeadsData(ReportService $reportService): array
    {
        return \App\Models\Lead::with(['status', 'urgency', 'serviceType'])
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
            })
            ->get()
            ->map(function ($lead) {
                return [
                    'ID' => $lead->id,
                    'Nome' => $lead->name,
                    'Telefone' => $lead->phone,
                    'E-mail' => $lead->email,
                    'Cidade' => $lead->city,
                    'Status' => $lead->status?->label,
                    'Urgência' => $lead->urgency?->label,
                    'Tipo de Serviço' => $lead->serviceType?->label,
                    'Origem' => $lead->source,
                    'Criado em' => $lead->created_at?->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    protected function getClientsData(ReportService $reportService): array
    {
        return \App\Models\Client::with(['lead'])
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
            })
            ->get()
            ->map(function ($client) {
                return [
                    'ID' => $client->id,
                    'Contato Principal' => $client->primary_contact,
                    'Telefone' => $client->phone,
                    'Cidade' => $client->city,
                    'Endereço' => $client->address,
                    'Lead Origem' => $client->lead?->name,
                    'Criado em' => $client->created_at?->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    protected function getDealsData(ReportService $reportService): array
    {
        return \App\Models\Deal::with(['lead', 'stage', 'status'])
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
            })
            ->get()
            ->map(function ($deal) {
                return [
                    'ID' => $deal->id,
                    'Lead' => $deal->lead?->name,
                    'Estágio' => $deal->stage?->name,
                    'Status' => $deal->status?->label,
                    'Valor Estimado' => 'R$ ' . number_format($deal->value_estimated, 2, ',', '.'),
                    'Criado em' => $deal->created_at?->format('d/m/Y H:i'),
                    'Atualizado em' => $deal->updated_at?->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    protected function getContractsData(ReportService $reportService): array
    {
        return \App\Models\Contract::with(['client', 'status', 'proposal'])
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('signed_at', [$this->startDate, $this->endDate]);
            })
            ->get()
            ->map(function ($contract) {
                return [
                    'ID' => $contract->id,
                    'Cliente' => $contract->client?->display_name,
                    'Status' => $contract->status?->label,
                    'Valor Mensal' => $contract->proposal ? 'R$ ' . number_format($contract->proposal->price, 2, ',', '.') : '-',
                    'Início' => $contract->start_date?->format('d/m/Y'),
                    'Término' => $contract->end_date?->format('d/m/Y'),
                    'Assinado em' => $contract->signed_at?->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    protected function generateFilename(): string
    {
        $timestamp = now()->format('Y-m-d_His');
        return "{$this->reportType}_{$timestamp}.{$this->format}";
    }

    protected function exportCsv(array $data, string $filepath): void
    {
        if (empty($data)) {
            Storage::put($filepath, '');
            return;
        }

        $headers = array_keys($data[0]);
        $csv = implode(',', $headers) . "\n";

        foreach ($data as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value ?? '') . '"';
            }, $row)) . "\n";
        }

        Storage::put($filepath, $csv);
    }
}
