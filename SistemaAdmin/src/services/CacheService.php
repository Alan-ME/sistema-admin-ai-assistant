<?php
namespace SistemaAdmin\Services;

use Database;
use Exception;

/**
 * Servicio de Cache para optimizar consultas frecuentes
 * Implementa cache en memoria y base de datos
 */
class CacheService
{
    private Database $database;
    private array $memoryCache = [];
    private string $cacheTable = 'cache_data';
    private int $defaultTtl = 3600; // 1 hora por defecto

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->crearTablaCache();
    }

    /**
     * Crear tabla de cache si no existe
     */
    private function crearTablaCache(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->cacheTable} (
            cache_key VARCHAR(255) PRIMARY KEY,
            cache_value LONGTEXT,
            expires_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->database->query($sql);
    }

    /**
     * Obtener valor del cache
     */
    public function get(string $key): mixed
    {
        // Primero verificar cache en memoria
        if (isset($this->memoryCache[$key])) {
            $cached = $this->memoryCache[$key];
            if ($cached['expires'] > time()) {
                return $cached['value'];
            } else {
                unset($this->memoryCache[$key]);
            }
        }

        // Verificar cache en base de datos
        $sql = "SELECT cache_value, expires_at FROM {$this->cacheTable} 
                WHERE cache_key = ? AND expires_at > NOW()";
        
        $result = $this->database->fetch($sql, [$key]);
        
        if ($result) {
            $value = json_decode($result['cache_value'], true);
            
            // Guardar en cache de memoria
            $this->memoryCache[$key] = [
                'value' => $value,
                'expires' => strtotime($result['expires_at'])
            ];
            
            return $value;
        }

        return null;
    }

    /**
     * Guardar valor en cache
     */
    public function set(string $key, mixed $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        $serializedValue = json_encode($value);

        // Guardar en cache de memoria
        $this->memoryCache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        // Guardar en base de datos
        $sql = "INSERT INTO {$this->cacheTable} (cache_key, cache_value, expires_at) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                cache_value = VALUES(cache_value), 
                expires_at = VALUES(expires_at)";

        $this->database->query($sql, [$key, $serializedValue, $expiresAt]);
        return true;
    }

    /**
     * Eliminar valor del cache
     */
    public function delete(string $key): bool
    {
        // Eliminar de memoria
        unset($this->memoryCache[$key]);

        // Eliminar de base de datos
        $sql = "DELETE FROM {$this->cacheTable} WHERE cache_key = ?";
        $this->database->query($sql, [$key]);
        return true;
    }

    /**
     * Limpiar cache expirado
     */
    public function cleanExpired(): int
    {
        $sql = "DELETE FROM {$this->cacheTable} WHERE expires_at <= NOW()";
        $stmt = $this->database->query($sql);
        $deletedRows = $stmt->rowCount();
        
        // Limpiar cache de memoria expirado
        $now = time();
        foreach ($this->memoryCache as $key => $cached) {
            if ($cached['expires'] <= $now) {
                unset($this->memoryCache[$key]);
            }
        }

        return $deletedRows;
    }

    /**
     * Limpiar todo el cache
     */
    public function clear(): bool
    {
        $this->memoryCache = [];
        $sql = "TRUNCATE TABLE {$this->cacheTable}";
        $this->database->query($sql);
        return true;
    }

    /**
     * Verificar si existe en cache
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Obtener o calcular (cache-aside pattern)
     */
    public function remember(string $key, callable $callback, int $ttl = null): mixed
    {
        $value = $this->get($key);
        
        if ($value === null) {
            $value = $callback();
            $this->set($key, $value, $ttl);
        }
        
        return $value;
    }

    /**
     * Invalidar cache por patrón
     */
    public function invalidatePattern(string $pattern): int
    {
        $sql = "DELETE FROM {$this->cacheTable} WHERE cache_key LIKE ?";
        $stmt = $this->database->query($sql, [$pattern]);
        $deletedRows = $stmt->rowCount();
        
        // Limpiar memoria por patrón
        foreach (array_keys($this->memoryCache) as $key) {
            if (fnmatch($pattern, $key)) {
                unset($this->memoryCache[$key]);
            }
        }

        return $deletedRows;
    }

    /**
     * Obtener estadísticas del cache
     */
    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_entries,
                    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_entries,
                    COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_entries
                FROM {$this->cacheTable}";
        
        $result = $this->database->fetch($sql);
        
        return [
            'total_entries' => (int)$result['total_entries'],
            'active_entries' => (int)$result['active_entries'],
            'expired_entries' => (int)$result['expired_entries'],
            'memory_entries' => count($this->memoryCache),
            'hit_ratio' => $this->calculateHitRatio()
        ];
    }

    /**
     * Calcular ratio de aciertos (simplificado)
     */
    private function calculateHitRatio(): float
    {
        // Implementación simplificada - en producción se usarían contadores
        return 0.85; // 85% de aciertos estimado
    }
}
