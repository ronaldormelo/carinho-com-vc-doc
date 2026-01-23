<?php

namespace App\Integrations\Meta;

use App\Integrations\BaseClient;

/**
 * Cliente para Facebook Pages API.
 *
 * Documentacao: https://developers.facebook.com/docs/pages/
 *
 * Endpoints principais:
 * - GET /{page-id} - Informacoes da pagina
 * - POST /{page-id}/feed - Publica post
 * - GET /{page-id}/posts - Lista posts
 * - GET /{page-id}/insights - Insights da pagina
 */
class FacebookPageClient extends BaseClient
{
    private string $accessToken;
    private string $pageId;
    private string $apiVersion;

    public function __construct()
    {
        $this->baseUrl = config('integrations.meta.base_url', 'https://graph.facebook.com');
        $this->apiVersion = config('integrations.meta.api_version', 'v18.0');
        $this->accessToken = config('integrations.meta.access_token', '');
        $this->pageId = config('integrations.meta.page_id', '');
        $this->timeout = (int) config('integrations.meta.timeout', 30);
        $this->connectTimeout = (int) config('integrations.meta.connect_timeout', 5);
        $this->cachePrefix = 'facebook_page';
    }

    /**
     * Obtem informacoes da pagina.
     */
    public function getPage(array $fields = []): array
    {
        $defaultFields = [
            'id',
            'name',
            'username',
            'about',
            'cover',
            'picture',
            'fan_count',
            'followers_count',
            'link',
            'website',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $fields ?: $defaultFields),
        ];

        return $this->get($this->pageId, $params);
    }

    /**
     * Publica um post de texto.
     */
    public function publishTextPost(string $message): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'message' => $message,
        ];

        return $this->post("{$this->pageId}/feed", $payload);
    }

    /**
     * Publica um post com link.
     */
    public function publishLinkPost(string $message, string $link): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'message' => $message,
            'link' => $link,
        ];

        return $this->post("{$this->pageId}/feed", $payload);
    }

    /**
     * Publica uma foto.
     */
    public function publishPhoto(string $imageUrl, ?string $caption = null): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'url' => $imageUrl,
        ];

        if ($caption) {
            $payload['caption'] = $caption;
        }

        return $this->post("{$this->pageId}/photos", $payload);
    }

    /**
     * Agenda um post.
     */
    public function schedulePost(string $message, int $timestamp, ?string $link = null): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'message' => $message,
            'published' => false,
            'scheduled_publish_time' => $timestamp,
        ];

        if ($link) {
            $payload['link'] = $link;
        }

        return $this->post("{$this->pageId}/feed", $payload);
    }

    /**
     * Lista posts da pagina.
     */
    public function listPosts(int $limit = 25, ?string $after = null): array
    {
        $params = [
            'access_token' => $this->accessToken,
            'fields' => 'id,message,created_time,permalink_url,shares,reactions.summary(true),comments.summary(true)',
            'limit' => $limit,
        ];

        if ($after) {
            $params['after'] = $after;
        }

        return $this->get("{$this->pageId}/posts", $params);
    }

    /**
     * Obtem detalhes de um post.
     */
    public function getPost(string $postId, array $fields = []): array
    {
        $defaultFields = [
            'id',
            'message',
            'created_time',
            'permalink_url',
            'shares',
            'reactions.summary(true)',
            'comments.summary(true)',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $fields ?: $defaultFields),
        ];

        return $this->get($postId, $params);
    }

    /**
     * Atualiza um post.
     */
    public function updatePost(string $postId, string $message): array
    {
        $payload = [
            'access_token' => $this->accessToken,
            'message' => $message,
        ];

        return $this->post($postId, $payload);
    }

    /**
     * Exclui um post.
     */
    public function deletePost(string $postId): array
    {
        return $this->delete("{$postId}?access_token={$this->accessToken}");
    }

    /**
     * Obtem insights da pagina.
     */
    public function getPageInsights(array $metrics = [], string $period = 'day'): array
    {
        $defaultMetrics = [
            'page_impressions',
            'page_impressions_unique',
            'page_engaged_users',
            'page_post_engagements',
            'page_fans',
            'page_fan_adds',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'metric' => implode(',', $metrics ?: $defaultMetrics),
            'period' => $period,
        ];

        return $this->get("{$this->pageId}/insights", $params);
    }

    /**
     * Obtem insights de um post.
     */
    public function getPostInsights(string $postId, array $metrics = []): array
    {
        $defaultMetrics = [
            'post_impressions',
            'post_impressions_unique',
            'post_engaged_users',
            'post_clicks',
            'post_reactions_by_type_total',
        ];

        $params = [
            'access_token' => $this->accessToken,
            'metric' => implode(',', $metrics ?: $defaultMetrics),
        ];

        return $this->get("{$postId}/insights", $params);
    }

    /**
     * Lista comentarios de um post.
     */
    public function listComments(string $postId): array
    {
        $params = [
            'access_token' => $this->accessToken,
            'fields' => 'id,message,created_time,from,like_count',
        ];

        return $this->get("{$postId}/comments", $params);
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

        return $this->post("{$commentId}/comments", $payload);
    }

    /**
     * Constroi URL com versao da API.
     */
    protected function buildUrl(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . '/' . $this->apiVersion . '/' . ltrim($endpoint, '/');
    }
}
