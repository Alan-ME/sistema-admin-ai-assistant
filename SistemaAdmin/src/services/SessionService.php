<?php

namespace SistemaAdmin\Services;

/**
 * Servicio de Sesiones
 * 
 * Maneja únicamente las operaciones de sesión
 */
class SessionService
{
    /**
     * Iniciar sesión
     */
    public function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verificar si hay sesión activa
     */
    public function tieneSesion(): bool
    {
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }

    /**
     * Obtener ID del usuario de la sesión
     */
    public function obtenerUsuarioId(): ?int
    {
        return $_SESSION['usuario_id'] ?? null;
    }

    /**
     * Obtener datos del usuario de la sesión
     */
    public function obtenerDatosUsuario(): ?array
    {
        if (!$this->tieneSesion()) {
            return null;
        }

        return [
            'id' => $_SESSION['usuario_id'],
            'username' => $_SESSION['username'] ?? null,
            'nombre' => $_SESSION['nombre'] ?? null,
            'apellido' => $_SESSION['apellido'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'rol' => $_SESSION['rol'] ?? null
        ];
    }

    /**
     * Establecer datos del usuario en la sesión
     */
    public function establecerUsuario(array $usuario): void
    {
        $this->iniciar();
        
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['apellido'] = $usuario['apellido'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['rol'] = $usuario['rol'];
    }

    /**
     * Cerrar sesión
     */
    public function cerrar(): void
    {
        $this->iniciar();
        
        // Limpiar todas las variables de sesión
        $_SESSION = [];
        
        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
    }

    /**
     * Regenerar ID de sesión
     */
    public function regenerarId(): void
    {
        $this->iniciar();
        session_regenerate_id(true);
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function tieneRol(string $rol): bool
    {
        return ($_SESSION['rol'] ?? '') === $rol;
    }

    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     */
    public function tieneAlgunRol(array $roles): bool
    {
        $rolUsuario = $_SESSION['rol'] ?? '';
        return in_array($rolUsuario, $roles);
    }

    /**
     * Obtener rol del usuario
     */
    public function obtenerRol(): ?string
    {
        return $_SESSION['rol'] ?? null;
    }

    /**
     * Establecer variable de sesión
     */
    public function establecer(string $clave, $valor): void
    {
        $this->iniciar();
        $_SESSION[$clave] = $valor;
    }

    /**
     * Obtener variable de sesión
     */
    public function obtener(string $clave, $default = null)
    {
        return $_SESSION[$clave] ?? $default;
    }

    /**
     * Eliminar variable de sesión
     */
    public function eliminar(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    /**
     * Verificar si existe una variable de sesión
     */
    public function existe(string $clave): bool
    {
        return isset($_SESSION[$clave]);
    }
}
