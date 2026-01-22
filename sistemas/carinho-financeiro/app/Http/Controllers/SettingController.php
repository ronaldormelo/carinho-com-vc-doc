<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\SettingCategory;
use App\Services\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Lista todas as configurações agrupadas por categoria.
     */
    public function index()
    {
        $settings = $this->settingService->getAll();

        return $this->successResponse($settings);
    }

    /**
     * Lista categorias de configuração.
     */
    public function categories()
    {
        $categories = SettingCategory::ordered()->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'description' => $category->description,
                'settings_count' => $category->settings()->count(),
            ];
        });

        return $this->successResponse($categories);
    }

    /**
     * Lista configurações de uma categoria.
     */
    public function byCategory(string $categoryCode)
    {
        $category = SettingCategory::getByCode($categoryCode);

        if (!$category) {
            return $this->notFoundResponse('Categoria não encontrada');
        }

        $settings = $category->settings()->ordered()->get()->map(function ($setting) {
            return [
                'id' => $setting->id,
                'key' => $setting->key,
                'name' => $setting->name,
                'description' => $setting->description,
                'value' => $setting->typed_value,
                'default_value' => $setting->typed_default_value,
                'value_type' => $setting->value_type,
                'unit' => $setting->unit,
                'is_editable' => $setting->is_editable,
                'is_public' => $setting->is_public,
                'is_default' => $setting->isUsingDefault(),
                'validation_rules' => $setting->validation_rules,
            ];
        });

        return $this->successResponse([
            'category' => [
                'code' => $category->code,
                'name' => $category->name,
                'description' => $category->description,
            ],
            'settings' => $settings,
        ]);
    }

    /**
     * Obtém uma configuração específica.
     */
    public function show(string $key)
    {
        $setting = Setting::getByKey($key);

        if (!$setting) {
            return $this->notFoundResponse('Configuração não encontrada');
        }

        return $this->successResponse([
            'id' => $setting->id,
            'key' => $setting->key,
            'name' => $setting->name,
            'description' => $setting->description,
            'value' => $setting->typed_value,
            'default_value' => $setting->typed_default_value,
            'value_type' => $setting->value_type,
            'unit' => $setting->unit,
            'is_editable' => $setting->is_editable,
            'is_public' => $setting->is_public,
            'is_default' => $setting->isUsingDefault(),
            'validation_rules' => $setting->validation_rules,
            'category' => [
                'code' => $setting->category->code,
                'name' => $setting->category->name,
            ],
        ]);
    }

    /**
     * Atualiza uma configuração.
     */
    public function update(Request $request, string $key)
    {
        $request->validate([
            'value' => 'required',
            'reason' => 'nullable|string|max:500',
        ]);

        $setting = Setting::getByKey($key);

        if (!$setting) {
            return $this->notFoundResponse('Configuração não encontrada');
        }

        if (!$setting->is_editable) {
            return $this->errorResponse('Esta configuração não pode ser editada', 403);
        }

        $changedBy = $request->user()?->name ?? $request->header('X-User-Name') ?? 'Sistema';

        $success = $this->settingService->set(
            $key,
            $request->value,
            $changedBy,
            $request->reason
        );

        if (!$success) {
            return $this->errorResponse('Não foi possível atualizar a configuração. Verifique o valor informado.', 422);
        }

        return $this->successResponse([
            'key' => $key,
            'value' => $this->settingService->get($key),
        ], 'Configuração atualizada com sucesso');
    }

    /**
     * Atualiza múltiplas configurações.
     */
    public function updateMany(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'reason' => 'nullable|string|max:500',
        ]);

        $changedBy = $request->user()?->name ?? $request->header('X-User-Name') ?? 'Sistema';
        $results = [];
        $errors = [];

        foreach ($request->settings as $item) {
            $setting = Setting::getByKey($item['key']);

            if (!$setting) {
                $errors[] = "Configuração '{$item['key']}' não encontrada";
                continue;
            }

            if (!$setting->is_editable) {
                $errors[] = "Configuração '{$item['key']}' não pode ser editada";
                continue;
            }

            $success = $this->settingService->set(
                $item['key'],
                $item['value'],
                $changedBy,
                $request->reason
            );

            $results[$item['key']] = $success;
        }

        return $this->successResponse([
            'results' => $results,
            'errors' => $errors,
            'updated_count' => count(array_filter($results)),
        ], 'Configurações processadas');
    }

    /**
     * Restaura configuração para valor padrão.
     */
    public function restoreDefault(Request $request, string $key)
    {
        $setting = Setting::getByKey($key);

        if (!$setting) {
            return $this->notFoundResponse('Configuração não encontrada');
        }

        if (!$setting->is_editable) {
            return $this->errorResponse('Esta configuração não pode ser editada', 403);
        }

        $changedBy = $request->user()?->name ?? $request->header('X-User-Name') ?? 'Sistema';

        $success = $this->settingService->restoreDefault($key, $changedBy);

        if (!$success) {
            return $this->errorResponse('Não foi possível restaurar a configuração', 422);
        }

        return $this->successResponse([
            'key' => $key,
            'value' => $this->settingService->get($key),
        ], 'Configuração restaurada para valor padrão');
    }

    /**
     * Restaura todas as configurações de uma categoria para valores padrão.
     */
    public function restoreCategoryDefaults(Request $request, string $categoryCode)
    {
        $category = SettingCategory::getByCode($categoryCode);

        if (!$category) {
            return $this->notFoundResponse('Categoria não encontrada');
        }

        $changedBy = $request->user()?->name ?? $request->header('X-User-Name') ?? 'Sistema';

        $count = $this->settingService->restoreCategoryDefaults($categoryCode, $changedBy);

        return $this->successResponse([
            'category' => $categoryCode,
            'restored_count' => $count,
        ], "{$count} configurações restauradas para valores padrão");
    }

    /**
     * Obtém histórico de uma configuração.
     */
    public function history(Request $request, string $key)
    {
        $setting = Setting::getByKey($key);

        if (!$setting) {
            return $this->notFoundResponse('Configuração não encontrada');
        }

        $limit = min($request->get('limit', 20), 100);
        $history = $this->settingService->getHistory($key, $limit);

        return $this->successResponse([
            'key' => $key,
            'name' => $setting->name,
            'history' => $history->map(function ($h) {
                return [
                    'old_value' => $h->old_value,
                    'new_value' => $h->new_value,
                    'changed_by' => $h->changed_by,
                    'change_reason' => $h->change_reason,
                    'changed_at' => $h->changed_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Obtém histórico geral de alterações.
     */
    public function allHistory(Request $request)
    {
        $limit = min($request->get('limit', 50), 200);
        $history = $this->settingService->getAllHistory($limit);

        return $this->successResponse($history->map(function ($h) {
            return [
                'setting_key' => $h->setting->key,
                'setting_name' => $h->setting->name,
                'category' => $h->setting->category->name,
                'old_value' => $h->old_value,
                'new_value' => $h->new_value,
                'changed_by' => $h->changed_by,
                'change_reason' => $h->change_reason,
                'changed_at' => $h->changed_at->toIso8601String(),
            ];
        }));
    }

    /**
     * Limpa cache de configurações.
     */
    public function clearCache()
    {
        $this->settingService->clearAllCache();

        return $this->successResponse(null, 'Cache de configurações limpo');
    }

    /*
    |--------------------------------------------------------------------------
    | Endpoints de Consulta de Configurações Específicas
    |--------------------------------------------------------------------------
    */

    /**
     * Obtém configurações de pagamento.
     */
    public function paymentConfig()
    {
        return $this->successResponse($this->settingService->getPaymentConfig());
    }

    /**
     * Obtém configurações de cancelamento.
     */
    public function cancellationConfig()
    {
        return $this->successResponse($this->settingService->getCancellationConfig());
    }

    /**
     * Obtém configurações de comissão.
     */
    public function commissionConfig()
    {
        return $this->successResponse($this->settingService->getCommissionConfig());
    }

    /**
     * Obtém configurações de precificação.
     */
    public function pricingConfig()
    {
        return $this->successResponse($this->settingService->getPricingConfig());
    }

    /**
     * Obtém configurações de margem.
     */
    public function marginConfig()
    {
        return $this->successResponse($this->settingService->getMarginConfig());
    }

    /**
     * Obtém configurações de repasse.
     */
    public function payoutConfig()
    {
        return $this->successResponse($this->settingService->getPayoutConfig());
    }

    /**
     * Obtém configurações públicas (para clientes).
     */
    public function publicSettings()
    {
        $settings = Setting::public()->with('category')->get();

        return $this->successResponse($settings->map(function ($setting) {
            return [
                'key' => $setting->key,
                'name' => $setting->name,
                'value' => $setting->typed_value,
                'unit' => $setting->unit,
                'category' => $setting->category->name,
            ];
        }));
    }
}
