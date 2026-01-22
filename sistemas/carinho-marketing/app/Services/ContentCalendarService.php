<?php

namespace App\Services;

use App\Models\ContentCalendar;
use App\Models\ContentAsset;
use App\Models\MarketingChannel;
use App\Models\Domain\DomainContentStatus;
use App\Models\Domain\DomainAssetStatus;
use App\Integrations\Meta\InstagramClient;
use App\Integrations\Meta\FacebookPageClient;
use App\Integrations\Internal\IntegracoesClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servico de gestao do calendario editorial.
 */
class ContentCalendarService
{
    public function __construct(
        private InstagramClient $instagram,
        private FacebookPageClient $facebook,
        private IntegracoesClient $integracoes
    ) {}

    /**
     * Lista itens do calendario por periodo.
     */
    public function listByPeriod(string $startDate, string $endDate, ?int $channelId = null): array
    {
        $query = ContentCalendar::with(['channel', 'status', 'assets'])
            ->inPeriod($startDate, $endDate)
            ->orderBy('scheduled_at');

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        return $query->get()->toArray();
    }

    /**
     * Lista itens da semana atual.
     */
    public function getThisWeek(?int $channelId = null): array
    {
        $query = ContentCalendar::with(['channel', 'status', 'assets'])
            ->thisWeek()
            ->orderBy('scheduled_at');

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        return $query->get()->toArray();
    }

    /**
     * Cria novo item no calendario.
     */
    public function create(array $data): ContentCalendar
    {
        return DB::transaction(function () use ($data) {
            $content = ContentCalendar::create([
                'channel_id' => $data['channel_id'],
                'title' => $data['title'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'status_id' => $data['status_id'] ?? DomainContentStatus::DRAFT,
                'owner_id' => $data['owner_id'] ?? null,
            ]);

            // Cria assets se fornecidos
            if (!empty($data['assets'])) {
                foreach ($data['assets'] as $assetData) {
                    $content->assets()->create([
                        'asset_type_id' => $assetData['type_id'],
                        'asset_url' => $assetData['url'],
                        'caption' => $assetData['caption'] ?? null,
                        'status_id' => DomainAssetStatus::DRAFT,
                    ]);
                }
            }

            Log::info('Content calendar item created', ['id' => $content->id]);

            return $content->load(['channel', 'status', 'assets']);
        });
    }

    /**
     * Atualiza item do calendario.
     */
    public function update(int $id, array $data): ContentCalendar
    {
        $content = ContentCalendar::findOrFail($id);

        if (!$content->isEditable()) {
            throw new \Exception('Conteudo nao pode ser editado.');
        }

        $content->update(array_filter([
            'channel_id' => $data['channel_id'] ?? null,
            'title' => $data['title'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status_id' => $data['status_id'] ?? null,
            'owner_id' => $data['owner_id'] ?? null,
        ], fn ($v) => $v !== null));

        return $content->load(['channel', 'status', 'assets']);
    }

    /**
     * Agenda conteudo para publicacao.
     */
    public function schedule(int $id, string $scheduledAt): ContentCalendar
    {
        $content = ContentCalendar::findOrFail($id);

        if ($content->status_id !== DomainContentStatus::DRAFT) {
            throw new \Exception('Apenas rascunhos podem ser agendados.');
        }

        if (!$content->assets()->exists()) {
            throw new \Exception('Conteudo precisa ter pelo menos um asset.');
        }

        $content->update([
            'scheduled_at' => $scheduledAt,
            'status_id' => DomainContentStatus::SCHEDULED,
        ]);

        Log::info('Content scheduled', [
            'id' => $content->id,
            'scheduled_at' => $scheduledAt,
        ]);

        return $content->fresh(['channel', 'status', 'assets']);
    }

    /**
     * Cancela agendamento.
     */
    public function cancelSchedule(int $id): ContentCalendar
    {
        $content = ContentCalendar::findOrFail($id);

        if ($content->status_id !== DomainContentStatus::SCHEDULED) {
            throw new \Exception('Apenas conteudos agendados podem ser cancelados.');
        }

        $content->update([
            'status_id' => DomainContentStatus::DRAFT,
        ]);

        return $content->fresh(['channel', 'status', 'assets']);
    }

    /**
     * Publica conteudo manualmente.
     */
    public function publish(int $id): array
    {
        $content = ContentCalendar::with(['channel', 'assets'])->findOrFail($id);

        if (!$content->assets()->exists()) {
            throw new \Exception('Conteudo precisa ter pelo menos um asset.');
        }

        $channelName = strtolower($content->channel->name ?? '');
        $result = [];

        try {
            if (str_contains($channelName, 'instagram')) {
                $result = $this->publishToInstagram($content);
            } elseif (str_contains($channelName, 'facebook')) {
                $result = $this->publishToFacebook($content);
            } else {
                throw new \Exception("Canal nao suportado: {$channelName}");
            }

            $content->update(['status_id' => DomainContentStatus::PUBLISHED]);

            // Atualiza assets como publicados
            $content->assets()->update(['status_id' => DomainAssetStatus::PUBLISHED]);

            // Dispara evento
            $this->integracoes->dispatchContentPublished(
                $content->id,
                $channelName,
                $content->toArray()
            );

            Log::info('Content published', [
                'id' => $content->id,
                'channel' => $channelName,
                'result' => $result,
            ]);

        } catch (\Throwable $e) {
            Log::error('Content publish failed', [
                'id' => $content->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return [
            'content' => $content->fresh(['channel', 'status', 'assets']),
            'platform_result' => $result,
        ];
    }

    /**
     * Publica no Instagram.
     */
    private function publishToInstagram(ContentCalendar $content): array
    {
        $asset = $content->assets->first();

        if (!$asset) {
            throw new \Exception('Nenhum asset encontrado.');
        }

        // Cria container
        if ($asset->isVideo()) {
            $containerResult = $this->instagram->createVideoContainer(
                $asset->asset_url,
                $asset->caption
            );
        } else {
            $containerResult = $this->instagram->createImageContainer(
                $asset->asset_url,
                $asset->caption
            );
        }

        if (!$containerResult['success']) {
            throw new \Exception('Erro ao criar container: ' . json_encode($containerResult));
        }

        $containerId = $containerResult['data']['id'] ?? null;

        if (!$containerId) {
            throw new \Exception('Container ID nao retornado.');
        }

        // Aguarda processamento e publica
        sleep(5); // Aguarda processamento do container

        $publishResult = $this->instagram->publishMedia($containerId);

        if (!$publishResult['success']) {
            throw new \Exception('Erro ao publicar: ' . json_encode($publishResult));
        }

        return $publishResult;
    }

    /**
     * Publica no Facebook.
     */
    private function publishToFacebook(ContentCalendar $content): array
    {
        $asset = $content->assets->first();

        if (!$asset) {
            throw new \Exception('Nenhum asset encontrado.');
        }

        if ($asset->isImage()) {
            return $this->facebook->publishPhoto($asset->asset_url, $asset->caption);
        }

        return $this->facebook->publishTextPost($asset->caption ?? $content->title);
    }

    /**
     * Processa conteudos pendentes de publicacao.
     */
    public function processPendingPublications(): array
    {
        $pending = ContentCalendar::pendingPublication()->get();
        $results = [];

        foreach ($pending as $content) {
            try {
                $results[$content->id] = $this->publish($content->id);
            } catch (\Throwable $e) {
                $results[$content->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Adiciona asset ao conteudo.
     */
    public function addAsset(int $contentId, array $assetData): ContentAsset
    {
        $content = ContentCalendar::findOrFail($contentId);

        if (!$content->isEditable()) {
            throw new \Exception('Conteudo nao pode ser editado.');
        }

        return $content->assets()->create([
            'asset_type_id' => $assetData['type_id'],
            'asset_url' => $assetData['url'],
            'caption' => $assetData['caption'] ?? null,
            'status_id' => DomainAssetStatus::DRAFT,
        ]);
    }

    /**
     * Remove asset do conteudo.
     */
    public function removeAsset(int $contentId, int $assetId): bool
    {
        $content = ContentCalendar::findOrFail($contentId);

        if (!$content->isEditable()) {
            throw new \Exception('Conteudo nao pode ser editado.');
        }

        return $content->assets()->where('id', $assetId)->delete() > 0;
    }

    /**
     * Aprova asset.
     */
    public function approveAsset(int $assetId): ContentAsset
    {
        $asset = ContentAsset::findOrFail($assetId);

        $asset->update(['status_id' => DomainAssetStatus::APPROVED]);

        return $asset->fresh();
    }

    /**
     * Estatisticas do calendario.
     */
    public function getStats(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->endOfMonth()->toDateString();

        return [
            'total' => ContentCalendar::inPeriod($startDate, $endDate)->count(),
            'draft' => ContentCalendar::inPeriod($startDate, $endDate)->draft()->count(),
            'scheduled' => ContentCalendar::inPeriod($startDate, $endDate)->scheduled()->count(),
            'published' => ContentCalendar::inPeriod($startDate, $endDate)->published()->count(),
            'by_channel' => ContentCalendar::inPeriod($startDate, $endDate)
                ->selectRaw('channel_id, COUNT(*) as total')
                ->groupBy('channel_id')
                ->pluck('total', 'channel_id')
                ->toArray(),
        ];
    }
}
