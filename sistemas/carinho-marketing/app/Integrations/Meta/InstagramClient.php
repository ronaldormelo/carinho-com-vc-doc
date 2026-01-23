<?php

namespace App\Integrations\Meta;

use App\Integrations\BaseClient;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para Instagram Graph API.
 *
 * Documentacao: https://developers.facebook.com/docs/instagram-api/
 *
 * Endpoints principais:
 * - GET /{ig-user-id} - Informacoes do usuario
 * - GET /{ig-user-id}/media - Lista de midias
 * - POST /{ig-user-id}/media - Cria container de midia
 * - POST /{ig-user-id}/media_publish - Publica midia
 * - GET /{ig-user-id}/insights - Insights do perfil
 * - GET /{ig-media-id}/insights - Insights da midia
 */
class InstagramClient extends BaseClient
{
    private string $accessToken;
    private string $businessAccountId;
    private string $apiVersion;

    public function __construct()
    {
        $this->baseUrl = config('integrations.meta.base_url', 'https://graph.facebook.com');
        $this->apiVersion = config('integrations.meta.api_version', 'v18.0');
        $this->accessToken = config('integrations.meta.access_token', '');
        $this->businessAccountId = config('integrations.instagram.business_account_id', '');
        $this->timeout = (int) config('integrations.meta.timeout', 30);
        $this->connectTimeout = (int) config('integrations.meta.connect_timeout', 5);
        $this->cachePrefix = 'instagram';
    }

    /**
     * Obtem informacoes do perfil do Instagram.
     */
    public function getProfile(array $fields = []): array
    {
        $defaultFields = [
            'id',
            'username',
            'name',
            'biography',
            'profile_picture_url',
            'followers_count',
            'follows_count',
            'media_count',
            'website',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $fields ?: $defaultFields),
        ];

        return $this->get($this->businessAccountId, $params);
    }

    /**
     * Lista midias do perfil.
     */
    public function listMedia(int $limit = 25, ?string $after = null): array
    {
        $params = [
            'access_token' => $this->accessToken,
            'fields' => 'id,caption,media_type,media_url,permalink,timestamp,thumbnail_url,like_count,comments_count',
            'limit' => $limit,
        ];

        if ($after) {
            $params['after'] = $after;
        }

        return $this->get("{$this->businessAccountId}/media", $params);
    }

    /**
     * Obtem detalhes de uma midia.
     */
    public function getMedia(string $mediaId, array $fields = []): array
    {
        $defaultFields = [
            'id',
            'caption',
            'media_type',
            'media_url',
            'permalink',
            'timestamp',
            'like_count',
            'comments_count',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $fields ?: $defaultFields),
        ];

        return $this->get($mediaId, $params);
    }

    /**
     * Cria container para publicacao de imagem.
     */
    public function createImageContainer(string $imageUrl, ?string $caption = null): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'image_url' => $imageUrl,
        ];

        if ($caption) {
            $payload['caption'] = $caption;
        }

        return $this->post("{$this->businessAccountId}/media", $payload);
    }

    /**
     * Cria container para publicacao de video (Reels).
     */
    public function createVideoContainer(string $videoUrl, ?string $caption = null, bool $isReel = false): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'video_url' => $videoUrl,
            'media_type' => $isReel ? 'REELS' : 'VIDEO',
        ];

        if ($caption) {
            $payload['caption'] = $caption;
        }

        return $this->post("{$this->businessAccountId}/media", $payload);
    }

    /**
     * Cria container para carrossel.
     */
    public function createCarouselContainer(array $childrenIds, ?string $caption = null): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childrenIds),
        ];

        if ($caption) {
            $payload['caption'] = $caption;
        }

        return $this->post("{$this->businessAccountId}/media", $payload);
    }

    /**
     * Cria container de item para carrossel.
     */
    public function createCarouselItemContainer(string $mediaUrl, bool $isVideo = false): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'is_carousel_item' => true,
        ];

        if ($isVideo) {
            $payload['video_url'] = $mediaUrl;
            $payload['media_type'] = 'VIDEO';
        } else {
            $payload['image_url'] = $mediaUrl;
        }

        return $this->post("{$this->businessAccountId}/media", $payload);
    }

    /**
     * Verifica status do container de midia.
     */
    public function getContainerStatus(string $containerId): array
    {
        $params = [
            'access_token' => $this->accessToken,
            'fields' => 'status_code,status',
        ];

        return $this->get($containerId, $params);
    }

    /**
     * Publica a midia (container deve estar pronto).
     */
    public function publishMedia(string $containerId): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'creation_id' => $containerId,
        ];

        return $this->post("{$this->businessAccountId}/media_publish", $payload);
    }

    /**
     * Obtem insights do perfil.
     */
    public function getProfileInsights(array $metrics = [], string $period = 'day'): array
    {
        $defaultMetrics = [
            'impressions',
            'reach',
            'profile_views',
            'follower_count',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'metric' => implode(',', $metrics ?: $defaultMetrics),
            'period' => $period,
        ];

        return $this->get("{$this->businessAccountId}/insights", $params);
    }

    /**
     * Obtem insights de uma midia.
     */
    public function getMediaInsights(string $mediaId, array $metrics = []): array
    {
        $defaultMetrics = [
            'impressions',
            'reach',
            'engagement',
            'saved',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'metric' => implode(',', $metrics ?: $defaultMetrics),
        ];

        return $this->get("{$mediaId}/insights", $params);
    }

    /**
     * Lista comentarios de uma midia.
     */
    public function listComments(string $mediaId): array
    {
        $params = [
            'access_token' => $this->accessToken,
            'fields' => 'id,text,timestamp,username,like_count',
        ];

        return $this->get("{$mediaId}/comments", $params);
    }

    /**
     * Responde a um comentario.
     */
    public function replyToComment(string $commentId, string $message): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'message' => $message,
        ];

        return $this->post("{$commentId}/replies", $payload);
    }

    /**
     * Busca hashtag.
     */
    public function searchHashtag(string $hashtag): array
    {
        $params = [
            'access_token' => $this->accessToken,
            'user_id' => $this->businessAccountId,
            'q' => $hashtag,
        ];

        return $this->get('ig_hashtag_search', $params);
    }

    /**
     * Obtem midias recentes de uma hashtag.
     */
    public function getHashtagRecentMedia(string $hashtagId, int $limit = 50): array
    {
        $params = [
            'access_token' => $this->accessToken,
            'user_id' => $this->businessAccountId,
            'fields' => 'id,caption,media_type,permalink,like_count,comments_count',
            'limit' => $limit,
        ];

        return $this->get("{$hashtagId}/recent_media", $params);
    }

    /**
     * Constroi URL com versao da API.
     */
    protected function buildUrl(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . '/' . $this->apiVersion . '/' . ltrim($endpoint, '/');
    }

    /**
     * Tipos de midia.
     */
    public static function getMediaTypes(): array
    {
        return [
            'IMAGE' => 'Imagem',
            'VIDEO' => 'Video',
            'CAROUSEL_ALBUM' => 'Carrossel',
            'REELS' => 'Reels',
        ];
    }
}
