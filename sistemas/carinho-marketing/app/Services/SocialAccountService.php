<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\MarketingChannel;
use App\Models\Domain\DomainChannelStatus;
use App\Integrations\Meta\InstagramClient;
use App\Integrations\Meta\FacebookPageClient;
use Illuminate\Support\Facades\Log;

/**
 * Servico de gestao de contas em redes sociais.
 */
class SocialAccountService
{
    public function __construct(
        private InstagramClient $instagram,
        private FacebookPageClient $facebook
    ) {}

    /**
     * Lista todas as contas.
     */
    public function list(array $filters = []): array
    {
        $query = SocialAccount::with(['channel', 'status']);

        if (!empty($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }

        if (!empty($filters['active'])) {
            $query->active();
        }

        return $query->orderBy('channel_id')->get()->toArray();
    }

    /**
     * Obtem conta por ID.
     */
    public function get(int $id): SocialAccount
    {
        return SocialAccount::with(['channel', 'status'])->findOrFail($id);
    }

    /**
     * Cria nova conta.
     */
    public function create(array $data): SocialAccount
    {
        $account = SocialAccount::create([
            'channel_id' => $data['channel_id'],
            'handle' => $data['handle'],
            'profile_url' => $data['profile_url'],
            'status_id' => $data['status_id'] ?? DomainChannelStatus::ACTIVE,
        ]);

        Log::info('Social account created', ['id' => $account->id]);

        return $account->load(['channel', 'status']);
    }

    /**
     * Atualiza conta.
     */
    public function update(int $id, array $data): SocialAccount
    {
        $account = SocialAccount::findOrFail($id);

        $account->update(array_filter([
            'handle' => $data['handle'] ?? null,
            'profile_url' => $data['profile_url'] ?? null,
            'status_id' => $data['status_id'] ?? null,
        ], fn ($v) => $v !== null));

        return $account->fresh(['channel', 'status']);
    }

    /**
     * Ativa conta.
     */
    public function activate(int $id): SocialAccount
    {
        $account = SocialAccount::findOrFail($id);
        $account->update(['status_id' => DomainChannelStatus::ACTIVE]);

        return $account->fresh(['channel', 'status']);
    }

    /**
     * Desativa conta.
     */
    public function deactivate(int $id): SocialAccount
    {
        $account = SocialAccount::findOrFail($id);
        $account->update(['status_id' => DomainChannelStatus::INACTIVE]);

        return $account->fresh(['channel', 'status']);
    }

    /**
     * Sincroniza informacoes do Instagram.
     */
    public function syncInstagramProfile(int $accountId): array
    {
        $account = SocialAccount::with('channel')->findOrFail($accountId);

        if (!str_contains(strtolower($account->channel->name ?? ''), 'instagram')) {
            throw new \Exception('Conta nao e do Instagram.');
        }

        try {
            $result = $this->instagram->getProfile();

            if ($result['success'] && !empty($result['data'])) {
                $profile = $result['data'];

                $account->update([
                    'handle' => $profile['username'] ?? $account->handle,
                    'profile_url' => "https://instagram.com/{$profile['username']}",
                ]);

                return [
                    'account' => $account->fresh()->toArray(),
                    'profile' => [
                        'username' => $profile['username'] ?? null,
                        'name' => $profile['name'] ?? null,
                        'biography' => $profile['biography'] ?? null,
                        'followers_count' => $profile['followers_count'] ?? 0,
                        'media_count' => $profile['media_count'] ?? 0,
                        'profile_picture_url' => $profile['profile_picture_url'] ?? null,
                    ],
                ];
            }

            return ['account' => $account->toArray(), 'error' => 'Falha ao obter perfil'];

        } catch (\Throwable $e) {
            Log::error('Instagram profile sync failed', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sincroniza informacoes do Facebook.
     */
    public function syncFacebookProfile(int $accountId): array
    {
        $account = SocialAccount::with('channel')->findOrFail($accountId);

        if (!str_contains(strtolower($account->channel->name ?? ''), 'facebook')) {
            throw new \Exception('Conta nao e do Facebook.');
        }

        try {
            $result = $this->facebook->getPage();

            if ($result['success'] && !empty($result['data'])) {
                $page = $result['data'];

                $account->update([
                    'handle' => $page['username'] ?? $page['name'] ?? $account->handle,
                    'profile_url' => $page['link'] ?? $account->profile_url,
                ]);

                return [
                    'account' => $account->fresh()->toArray(),
                    'profile' => [
                        'name' => $page['name'] ?? null,
                        'username' => $page['username'] ?? null,
                        'about' => $page['about'] ?? null,
                        'fan_count' => $page['fan_count'] ?? 0,
                        'followers_count' => $page['followers_count'] ?? 0,
                        'link' => $page['link'] ?? null,
                    ],
                ];
            }

            return ['account' => $account->toArray(), 'error' => 'Falha ao obter pagina'];

        } catch (\Throwable $e) {
            Log::error('Facebook profile sync failed', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtem bio padrao formatada para a conta.
     */
    public function getFormattedBio(int $accountId): array
    {
        $account = SocialAccount::with('channel')->findOrFail($accountId);

        $bioTemplate = config('branding.social.bio_template', '');
        $bioUrl = $account->bio_url;

        return [
            'account' => $account->toArray(),
            'bio_template' => $bioTemplate,
            'bio_url' => $bioUrl,
            'hashtags' => config('branding.social.hashtags', []),
        ];
    }

    /**
     * Lista canais de marketing disponiveis.
     */
    public function listChannels(): array
    {
        return MarketingChannel::with('status')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Cria canal de marketing.
     */
    public function createChannel(string $name): MarketingChannel
    {
        return MarketingChannel::create([
            'name' => $name,
            'status_id' => DomainChannelStatus::ACTIVE,
        ]);
    }

    /**
     * Estatisticas de contas.
     */
    public function getStats(): array
    {
        return [
            'total' => SocialAccount::count(),
            'active' => SocialAccount::active()->count(),
            'by_channel' => SocialAccount::selectRaw('channel_id, COUNT(*) as total')
                ->groupBy('channel_id')
                ->pluck('total', 'channel_id')
                ->toArray(),
        ];
    }
}
