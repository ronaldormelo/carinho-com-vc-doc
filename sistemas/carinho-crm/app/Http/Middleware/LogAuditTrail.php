<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware para registrar trilha de auditoria de acessos
 */
class LogAuditTrail
{
    /**
     * Rotas que devem ser auditadas
     */
    protected array $auditRoutes = [
        'leads.*',
        'clients.*',
        'contracts.*',
        'deals.*',
        'reports.*',
    ];

    /**
     * Métodos que indicam modificação de dados
     */
    protected array $mutationMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Só audita rotas específicas
        if (!$this->shouldAudit($request)) {
            return $response;
        }

        $this->logAccess($request, $response);

        return $response;
    }

    protected function shouldAudit(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        
        if (!$routeName) {
            return false;
        }

        foreach ($this->auditRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    protected function logAccess(Request $request, Response $response): void
    {
        $user = $request->user();
        $isMutation = in_array($request->method(), $this->mutationMethods);

        $logData = [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => $request->route()?->getName(),
            'status_code' => $response->getStatusCode(),
            'is_mutation' => $isMutation,
        ];

        // Para mutações, inclui parâmetros (sem dados sensíveis)
        if ($isMutation) {
            $params = $request->except(['password', 'token', 'secret']);
            // Remove campos sensíveis
            unset($params['phone'], $params['email'], $params['address']);
            $logData['params_keys'] = array_keys($params);
        }

        Log::channel('audit')->info(
            $isMutation ? 'Data mutation' : 'Data access',
            $logData
        );
    }
}
