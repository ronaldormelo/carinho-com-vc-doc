<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware de autenticacao por API Key.
 *
 * Valida API Key enviada no header X-API-Key.
 */
class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            Log::warning('API request without API key', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'API key is required',
            ], 401);
        }

        // Busca todas as chaves ativas e valida
        $keys = ApiKey::active()->get();
        $matchedKey = null;

        foreach ($keys as $key) {
            if ($key->validateKey($apiKey)) {
                $matchedKey = $key;
                break;
            }
        }

        if (!$matchedKey) {
            Log::warning('Invalid API key', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Invalid API key',
            ], 401);
        }

        // Verifica permissao especifica se requerida
        if ($permission && !$matchedKey->hasPermission($permission)) {
            Log::warning('API key lacks permission', [
                'key_name' => $matchedKey->name,
                'permission' => $permission,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Insufficient permissions',
            ], 403);
        }

        // Atualiza ultimo uso
        $matchedKey->markAsUsed();

        // Adiciona informacao da chave ao request
        $request->attributes->set('api_key', $matchedKey);
        $request->attributes->set('api_key_name', $matchedKey->name);

        return $next($request);
    }
}
