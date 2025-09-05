<?php

namespace SistemaAdmin\Services;

/**
 * Servicio de Seguridad
 * 
 * Maneja tokens CSRF, rate limiting y otras medidas de seguridad
 */
class ServicioSeguridad
{
    private $database;
    private $sessionKey = 'csrf_token';
    private $rateLimitKey = 'rate_limit_';
    private $maxAttempts = 5;
    private $timeWindow = 300; // 5 minutos

    public function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * Generar token CSRF
     */
    public function generarTokenCSRF(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[$this->sessionKey] = $token;
        
        return $token;
    }

    /**
     * Verificar token CSRF
     */
    public function verificarTokenCSRF(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$this->sessionKey])) {
            return false;
        }

        return hash_equals($_SESSION[$this->sessionKey], $token);
    }

    /**
     * Obtener token CSRF actual
     */
    public function obtenerTokenCSRF(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION[$this->sessionKey] ?? null;
    }

    /**
     * Verificar rate limiting
     */
    public function verificarRateLimit(string $identifier, string $action = 'default'): array
    {
        $key = $this->rateLimitKey . $action . '_' . $identifier;
        $now = time();

        // Obtener intentos actuales
        $attempts = $this->obtenerIntentos($key);

        // Limpiar intentos antiguos
        $attempts = array_filter($attempts, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });

        // Verificar si excede el límite
        if (count($attempts) >= $this->maxAttempts) {
            $tiempoRestante = $this->timeWindow - ($now - min($attempts));
            
            return [
                'allowed' => false,
                'attempts' => count($attempts),
                'max_attempts' => $this->maxAttempts,
                'time_remaining' => $tiempoRestante,
                'message' => "Demasiados intentos. Intente nuevamente en {$tiempoRestante} segundos."
            ];
        }

        // Registrar intento actual
        $attempts[] = $now;
        $this->guardarIntentos($key, $attempts);

        return [
            'allowed' => true,
            'attempts' => count($attempts),
            'max_attempts' => $this->maxAttempts,
            'time_remaining' => 0
        ];
    }

    /**
     * Limpiar rate limiting para un identificador
     */
    public function limpiarRateLimit(string $identifier, string $action = 'default'): void
    {
        $key = $this->rateLimitKey . $action . '_' . $identifier;
        $this->guardarIntentos($key, []);
    }

    /**
     * Obtener intentos de rate limiting
     */
    private function obtenerIntentos(string $key): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION[$key] ?? [];
    }

    /**
     * Guardar intentos de rate limiting
     */
    private function guardarIntentos(string $key, array $attempts): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[$key] = $attempts;
    }

    /**
     * Sanitizar entrada de usuario
     */
    public function sanitizarEntrada(string $input): string
    {
        // Remover caracteres de control
        $input = preg_replace('/[\x00-\x1F\x7F]/', '', $input);
        
        // Escapar HTML
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        // Limitar longitud
        $input = substr($input, 0, 1000);
        
        return trim($input);
    }

    /**
     * Validar entrada de usuario
     */
    public function validarEntrada(string $input, array $reglas = []): array
    {
        $errores = [];

        // Validación básica
        if (empty(trim($input))) {
            $errores[] = 'El campo no puede estar vacío';
        }

        // Validaciones específicas
        foreach ($reglas as $regla => $valor) {
            switch ($regla) {
                case 'min_length':
                    if (strlen($input) < $valor) {
                        $errores[] = "El campo debe tener al menos {$valor} caracteres";
                    }
                    break;
                case 'max_length':
                    if (strlen($input) > $valor) {
                        $errores[] = "El campo no puede exceder {$valor} caracteres";
                    }
                    break;
                case 'email':
                    if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                        $errores[] = 'El formato del email no es válido';
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($input)) {
                        $errores[] = 'El campo debe ser numérico';
                    }
                    break;
                case 'regex':
                    if (!preg_match($valor, $input)) {
                        $errores[] = 'El formato del campo no es válido';
                    }
                    break;
            }
        }

        return $errores;
    }

    /**
     * Generar hash seguro para contraseñas
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iteraciones
            'threads' => 3          // 3 hilos
        ]);
    }

    /**
     * Verificar contraseña
     */
    public function verificarPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generar token de recuperación de contraseña
     */
    public function generarTokenRecuperacion(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validar token de recuperación
     */
    public function validarTokenRecuperacion(string $token): bool
    {
        // Verificar formato
        if (strlen($token) !== 64) {
            return false;
        }

        // Verificar que solo contenga caracteres hexadecimales
        return ctype_xdigit($token);
    }

    /**
     * Obtener IP del cliente
     */
    public function obtenerIPCliente(): string
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
     * Verificar si la IP está en lista negra
     */
    public function verificarIPListaNegra(string $ip): bool
    {
        // Lista de IPs bloqueadas (en producción esto vendría de la base de datos)
        $ipsBloqueadas = [
            '127.0.0.1', // Solo para testing
        ];

        return in_array($ip, $ipsBloqueadas);
    }

    /**
     * Configurar headers de seguridad
     */
    public function configurarHeadersSeguridad(): void
    {
        // Prevenir clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevenir MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Habilitar XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Configurar Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'");
        
        // Configurar Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Configurar Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    /**
     * Limpiar tokens CSRF expirados
     */
    public function limpiarTokensExpirados(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Limpiar tokens CSRF si la sesión es muy antigua (más de 1 hora)
        if (isset($_SESSION['csrf_token_time'])) {
            if (time() - $_SESSION['csrf_token_time'] > 3600) {
                unset($_SESSION[$this->sessionKey]);
                unset($_SESSION['csrf_token_time']);
            }
        } else {
            $_SESSION['csrf_token_time'] = time();
        }
    }
}
