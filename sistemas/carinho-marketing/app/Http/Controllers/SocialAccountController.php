<?php

namespace App\Http\Controllers;

use App\Services\SocialAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    public function __construct(
        private SocialAccountService $service
    ) {}

    /**
     * Lista contas sociais.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['channel_id', 'active']);

        $accounts = $this->service->list($filters);

        return $this->success($accounts, 'Contas carregadas');
    }

    /**
     * Cria nova conta.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'channel_id' => 'required|integer|exists:marketing_channels,id',
            'handle' => 'required|string|max:128',
            'profile_url' => 'required|string|url|max:512',
        ]);

        try {
            $account = $this->service->create($request->all());

            return $this->created($account->toArray(), 'Conta criada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Exibe conta.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $account = $this->service->get($id);

            return $this->success($account->toArray());
        } catch (\Throwable $e) {
            return $this->notFound('Conta nao encontrada');
        }
    }

    /**
     * Atualiza conta.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'handle' => 'nullable|string|max:128',
            'profile_url' => 'nullable|string|url|max:512',
        ]);

        try {
            $account = $this->service->update($id, $request->all());

            return $this->success($account->toArray(), 'Conta atualizada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Ativa conta.
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $account = $this->service->activate($id);

            return $this->success($account->toArray(), 'Conta ativada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Desativa conta.
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $account = $this->service->deactivate($id);

            return $this->success($account->toArray(), 'Conta desativada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Sincroniza perfil do Instagram.
     */
    public function syncInstagram(int $id): JsonResponse
    {
        try {
            $result = $this->service->syncInstagramProfile($id);

            return $this->success($result, 'Perfil sincronizado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Sincroniza perfil do Facebook.
     */
    public function syncFacebook(int $id): JsonResponse
    {
        try {
            $result = $this->service->syncFacebookProfile($id);

            return $this->success($result, 'Pagina sincronizada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Obtem bio formatada.
     */
    public function bio(int $id): JsonResponse
    {
        try {
            $bio = $this->service->getFormattedBio($id);

            return $this->success($bio, 'Bio carregada');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Lista canais de marketing.
     */
    public function channels(): JsonResponse
    {
        $channels = $this->service->listChannels();

        return $this->success($channels, 'Canais carregados');
    }

    /**
     * Cria canal de marketing.
     */
    public function createChannel(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:128',
        ]);

        try {
            $channel = $this->service->createChannel($request->input('name'));

            return $this->created($channel->toArray(), 'Canal criado');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Estatisticas de contas.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->service->getStats();

        return $this->success($stats, 'Estatisticas carregadas');
    }
}
