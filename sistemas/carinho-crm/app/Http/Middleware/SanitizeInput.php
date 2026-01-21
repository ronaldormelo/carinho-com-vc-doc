<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para sanitizar inputs e prevenir XSS
 */
class SanitizeInput
{
    /**
     * Campos que não devem ser sanitizados
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'preferences_json',
        'conditions_json',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        
        $sanitized = $this->sanitizeArray($input);
        
        $request->merge($sanitized);

        return $next($request);
    }

    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->except)) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }

        return $data;
    }

    protected function sanitizeString(string $value): string
    {
        // Remove tags HTML
        $value = strip_tags($value);
        
        // Converte caracteres especiais para entidades HTML
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        // Remove caracteres de controle (exceto newlines e tabs)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Trim
        $value = trim($value);

        return $value;
    }
}
