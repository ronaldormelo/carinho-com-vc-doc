<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Mapeamento de transformacao de eventos entre sistemas.
 *
 * @property int $id
 * @property string $event_type
 * @property string $target_system
 * @property array $mapping_json
 * @property string $version
 */
class EventMapping extends Model
{
    public $timestamps = false;

    protected $table = 'event_mappings';

    protected $fillable = [
        'event_type',
        'target_system',
        'mapping_json',
        'version',
    ];

    protected $casts = [
        'mapping_json' => 'array',
    ];

    /**
     * Aplica mapeamento ao payload de origem.
     */
    public function transform(array $sourcePayload): array
    {
        $mapping = $this->mapping_json;
        $result = [];

        foreach ($mapping as $targetKey => $sourceKey) {
            if (is_string($sourceKey)) {
                // Mapeamento simples: chave -> valor
                $result[$targetKey] = data_get($sourcePayload, $sourceKey);
            } elseif (is_array($sourceKey)) {
                // Mapeamento com transformacao
                $result[$targetKey] = $this->applyTransformation($sourcePayload, $sourceKey);
            }
        }

        return $result;
    }

    /**
     * Aplica transformacao especial.
     */
    private function applyTransformation(array $payload, array $config): mixed
    {
        $type = $config['type'] ?? 'direct';
        $source = $config['source'] ?? null;
        $default = $config['default'] ?? null;

        $value = $source ? data_get($payload, $source) : null;

        return match ($type) {
            'direct' => $value ?? $default,
            'concat' => $this->transformConcat($payload, $config),
            'map' => $this->transformMap($value, $config),
            'date' => $this->transformDate($value, $config),
            'number' => $this->transformNumber($value, $config),
            'boolean' => (bool) $value,
            'array' => $this->transformArray($payload, $config),
            'static' => $config['value'] ?? $default,
            default => $value ?? $default,
        };
    }

    /**
     * Concatena multiplos campos.
     */
    private function transformConcat(array $payload, array $config): string
    {
        $sources = $config['sources'] ?? [];
        $separator = $config['separator'] ?? ' ';

        $values = array_map(
            fn($key) => data_get($payload, $key),
            $sources
        );

        return implode($separator, array_filter($values));
    }

    /**
     * Mapeia valores usando tabela de traducao.
     */
    private function transformMap($value, array $config): mixed
    {
        $map = $config['map'] ?? [];
        $default = $config['default'] ?? $value;

        return $map[$value] ?? $default;
    }

    /**
     * Transforma data para formato especificado.
     */
    private function transformDate($value, array $config): ?string
    {
        if (!$value) {
            return null;
        }

        $format = $config['format'] ?? 'Y-m-d H:i:s';

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Converte para numero.
     */
    private function transformNumber($value, array $config): float|int|null
    {
        if (!is_numeric($value)) {
            return $config['default'] ?? null;
        }

        $type = $config['number_type'] ?? 'float';

        return $type === 'int' ? (int) $value : (float) $value;
    }

    /**
     * Transforma em array.
     */
    private function transformArray(array $payload, array $config): array
    {
        $sources = $config['sources'] ?? [];
        $result = [];

        foreach ($sources as $key => $source) {
            if (is_numeric($key)) {
                $result[] = data_get($payload, $source);
            } else {
                $result[$key] = data_get($payload, $source);
            }
        }

        return $result;
    }

    /**
     * Busca mapeamento para evento e sistema alvo.
     */
    public static function forEvent(string $eventType, string $targetSystem): ?self
    {
        return self::where('event_type', $eventType)
            ->where('target_system', $targetSystem)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Lista todas as versoes de um mapeamento.
     */
    public static function versions(string $eventType, string $targetSystem)
    {
        return self::where('event_type', $eventType)
            ->where('target_system', $targetSystem)
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Cria nova versao do mapeamento.
     */
    public static function createVersion(string $eventType, string $targetSystem, array $mapping): self
    {
        $latestVersion = self::where('event_type', $eventType)
            ->where('target_system', $targetSystem)
            ->max('version') ?? '0.0.0';

        $parts = explode('.', $latestVersion);
        $parts[2] = ((int) ($parts[2] ?? 0)) + 1;
        $newVersion = implode('.', $parts);

        return self::create([
            'event_type' => $eventType,
            'target_system' => $targetSystem,
            'mapping_json' => $mapping,
            'version' => $newVersion,
        ]);
    }
}
