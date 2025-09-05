<?php

namespace SistemaAdmin\Services;

/**
 * Servicio de Logging
 * 
 * Maneja el registro de eventos de seguridad y auditoría
 */
class ServicioLogging
{
    private $database;
    private $logDirectory;
    private $maxLogSize = 10485760; // 10MB
    private $maxLogFiles = 5;

    public function __construct($database, $logDirectory = 'logs')
    {
        $this->database = $database;
        $this->logDirectory = $logDirectory;
        
        // Crear directorio de logs si no existe
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0755, true);
        }
    }

    /**
     * Registrar evento de seguridad
     */
    public function registrarEventoSeguridad(string $tipo, string $descripcion, array $datos = []): void
    {
        $evento = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'ip' => $this->obtenerIPCliente(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'datos' => $datos
        ];

        // Guardar en archivo de log
        $this->escribirLogArchivo('security.log', $evento);

        // Guardar en base de datos si es crítico
        if (in_array($tipo, ['LOGIN_FAILED', 'UNAUTHORIZED_ACCESS', 'CSRF_ATTACK', 'RATE_LIMIT_EXCEEDED'])) {
            $this->guardarEnBaseDatos($evento);
        }
    }

    /**
     * Registrar evento de auditoría
     */
    public function registrarEventoAuditoria(string $accion, string $entidad, int $entidadId = null, array $datos = []): void
    {
        $evento = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => 'AUDIT',
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'ip' => $this->obtenerIPCliente(),
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'datos' => $datos
        ];

        // Guardar en archivo de log
        $this->escribirLogArchivo('audit.log', $evento);

        // Guardar en base de datos
        $this->guardarEnBaseDatos($evento);
    }

    /**
     * Registrar error del sistema
     */
    public function registrarError(string $mensaje, string $archivo = '', int $linea = 0, array $contexto = []): void
    {
        $evento = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => 'ERROR',
            'mensaje' => $mensaje,
            'archivo' => $archivo,
            'linea' => $linea,
            'ip' => $this->obtenerIPCliente(),
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'contexto' => $contexto
        ];

        // Guardar en archivo de log
        $this->escribirLogArchivo('error.log', $evento);
    }

    /**
     * Obtener logs de seguridad
     */
    public function obtenerLogsSeguridad(int $limite = 100, string $tipo = null): array
    {
        $sql = "SELECT * FROM logs_seguridad";
        $params = [];

        if ($tipo) {
            $sql .= " WHERE tipo = ?";
            $params[] = $tipo;
        }

        $sql .= " ORDER BY timestamp DESC LIMIT ?";
        $params[] = $limite;

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * Obtener logs de auditoría
     */
    public function obtenerLogsAuditoria(int $limite = 100, string $entidad = null): array
    {
        $sql = "SELECT * FROM logs_auditoria";
        $params = [];

        if ($entidad) {
            $sql .= " WHERE entidad = ?";
            $params[] = $entidad;
        }

        $sql .= " ORDER BY timestamp DESC LIMIT ?";
        $params[] = $limite;

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * Limpiar logs antiguos
     */
    public function limpiarLogsAntiguos(int $dias = 30): int
    {
        $fecha = date('Y-m-d H:i:s', strtotime("-{$dias} days"));
        
        $sql = "DELETE FROM logs_seguridad WHERE timestamp < ?";
        $stmt = $this->database->query($sql, [$fecha]);
        $eliminados = $stmt->rowCount();
        
        $sql = "DELETE FROM logs_auditoria WHERE timestamp < ?";
        $stmt = $this->database->query($sql, [$fecha]);
        $eliminados += $stmt->rowCount();
        
        return $eliminados;
    }

    /**
     * Escribir log en archivo
     */
    private function escribirLogArchivo(string $archivo, array $evento): void
    {
        $rutaArchivo = $this->logDirectory . '/' . $archivo;
        
        // Rotar archivo si es muy grande
        if (file_exists($rutaArchivo) && filesize($rutaArchivo) > $this->maxLogSize) {
            $this->rotarArchivoLog($rutaArchivo);
        }

        $linea = json_encode($evento) . "\n";
        file_put_contents($rutaArchivo, $linea, FILE_APPEND | LOCK_EX);
    }

    /**
     * Rotar archivo de log
     */
    private function rotarArchivoLog(string $archivo): void
    {
        // Mover archivos existentes
        for ($i = $this->maxLogFiles - 1; $i > 0; $i--) {
            $archivoActual = $archivo . '.' . $i;
            $archivoSiguiente = $archivo . '.' . ($i + 1);
            
            if (file_exists($archivoActual)) {
                if ($i === $this->maxLogFiles - 1) {
                    unlink($archivoActual);
                } else {
                    rename($archivoActual, $archivoSiguiente);
                }
            }
        }

        // Mover archivo principal
        if (file_exists($archivo)) {
            rename($archivo, $archivo . '.1');
        }
    }

    /**
     * Guardar evento en base de datos
     */
    private function guardarEnBaseDatos(array $evento): void
    {
        try {
            if ($evento['tipo'] === 'AUDIT') {
                $sql = "INSERT INTO logs_auditoria (timestamp, accion, entidad, entidad_id, ip, usuario_id, datos) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $evento['timestamp'],
                    $evento['accion'],
                    $evento['entidad'],
                    $evento['entidad_id'],
                    $evento['ip'],
                    $evento['usuario_id'],
                    json_encode($evento['datos'])
                ];
            } else {
                $sql = "INSERT INTO logs_seguridad (timestamp, tipo, descripcion, ip, usuario_id, datos) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $params = [
                    $evento['timestamp'],
                    $evento['tipo'],
                    $evento['descripcion'],
                    $evento['ip'],
                    $evento['usuario_id'],
                    json_encode($evento['datos'])
                ];
            }

            $this->database->query($sql, $params);
        } catch (\Exception $e) {
            // Si falla la base de datos, al menos guardar en archivo
            error_log("Error guardando log en BD: " . $e->getMessage());
        }
    }

    /**
     * Obtener IP del cliente
     */
    private function obtenerIPCliente(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Crear tablas de logs si no existen
     */
    public function crearTablasLogs(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS logs_seguridad (
                id INT AUTO_INCREMENT PRIMARY KEY,
                timestamp DATETIME NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                descripcion TEXT NOT NULL,
                ip VARCHAR(45) NOT NULL,
                usuario_id INT NULL,
                datos JSON NULL,
                INDEX idx_timestamp (timestamp),
                INDEX idx_tipo (tipo),
                INDEX idx_usuario (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $this->database->query($sql);

        $sql = "
            CREATE TABLE IF NOT EXISTS logs_auditoria (
                id INT AUTO_INCREMENT PRIMARY KEY,
                timestamp DATETIME NOT NULL,
                accion VARCHAR(100) NOT NULL,
                entidad VARCHAR(100) NOT NULL,
                entidad_id INT NULL,
                ip VARCHAR(45) NOT NULL,
                usuario_id INT NULL,
                datos JSON NULL,
                INDEX idx_timestamp (timestamp),
                INDEX idx_entidad (entidad),
                INDEX idx_usuario (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $this->database->query($sql);
    }
}
