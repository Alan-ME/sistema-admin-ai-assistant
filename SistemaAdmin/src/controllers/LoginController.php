<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioSeguridad;
use SistemaAdmin\Services\ServicioLogging;

/**
 * Controller para manejar las peticiones HTTP relacionadas con autenticación
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para autenticación.
 */
class LoginController
{
    private ServicioAutenticacion $servicioAutenticacion;
    private ServicioSeguridad $servicioSeguridad;
    private ServicioLogging $servicioLogging;

    public function __construct(ServicioAutenticacion $servicioAutenticacion, ServicioSeguridad $servicioSeguridad, ServicioLogging $servicioLogging)
    {
        $this->servicioAutenticacion = $servicioAutenticacion;
        $this->servicioSeguridad = $servicioSeguridad;
        $this->servicioLogging = $servicioLogging;
    }

    /**
     * Maneja la petición POST para autenticar un usuario
     */
    public function autenticar(array $datos): array
    {
        try {
            // Validar datos requeridos
            $errores = $this->validarDatosLogin($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }

            // Verificar rate limiting
            $ip = $this->servicioSeguridad->obtenerIPCliente();
            $rateLimit = $this->servicioSeguridad->verificarRateLimit($ip, 'login');
            
            if (!$rateLimit['allowed']) {
                // Registrar intento de rate limiting
                $this->servicioLogging->registrarEventoSeguridad(
                    'RATE_LIMIT_EXCEEDED',
                    'Intento de login excedió el límite de rate limiting',
                    ['username' => $username, 'attempts' => $rateLimit['attempts']]
                );
                
                return [
                    'success' => false,
                    'error' => $rateLimit['message'],
                    'rate_limited' => true
                ];
            }

            // Verificar token CSRF si está presente
            if (isset($datos['csrf_token'])) {
                if (!$this->servicioSeguridad->verificarTokenCSRF($datos['csrf_token'])) {
                    // Registrar intento de ataque CSRF
                    $this->servicioLogging->registrarEventoSeguridad(
                        'CSRF_ATTACK',
                        'Intento de ataque CSRF detectado',
                        ['username' => $username, 'token' => $datos['csrf_token']]
                    );
                    
                    return [
                        'success' => false,
                        'error' => 'Token de seguridad inválido'
                    ];
                }
            }
            
            // Autenticar usuario
            $resultado = $this->servicioAutenticacion->autenticar(
                $datos['username'], 
                $datos['password']
            );
            
            if ($resultado['success']) {
                // Limpiar rate limiting en caso de éxito
                $this->servicioSeguridad->limpiarRateLimit($ip, 'login');

                // Registrar login exitoso
                $this->servicioLogging->registrarEventoSeguridad(
                    'LOGIN_SUCCESS',
                    'Login exitoso',
                    ['username' => $username, 'user_id' => $resultado['data']['id']]
                );

                // Iniciar sesión solo si no está ya iniciada
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['usuario_id'] = $resultado['data']['id'];
                $_SESSION['username'] = $resultado['data']['username'];
                $_SESSION['rol'] = $resultado['data']['rol'];
                $_SESSION['nombre'] = $resultado['data']['nombre'];
                $_SESSION['apellido'] = $resultado['data']['apellido'];
                
                // Actualizar último acceso
                $this->servicioAutenticacion->actualizarUltimoAcceso($resultado['data']['id']);
                
                return [
                    'success' => true,
                    'data' => $resultado['data'],
                    'message' => 'Autenticación exitosa'
                ];
            } else {
                // Registrar login fallido
                $this->servicioLogging->registrarEventoSeguridad(
                    'LOGIN_FAILED',
                    'Intento de login fallido',
                    ['username' => $username, 'error' => $resultado['error']]
                );
                
                return [
                    'success' => false,
                    'error' => $resultado['error']
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Maneja la petición POST para cerrar sesión
     */
    public function cerrarSesion(): array
    {
        try {
            $resultado = $this->servicioAutenticacion->cerrarSesion();
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Sesión cerrada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Error al cerrar la sesión'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Maneja la petición GET para verificar el estado de la sesión
     */
    public function verificarSesion(): array
    {
        try {
            $usuario = $this->servicioAutenticacion->verificarSesion();
            
            if ($usuario) {
                return [
                    'success' => true,
                    'data' => $usuario,
                    'message' => 'Sesión activa'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No hay sesión activa'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Maneja la petición POST para cambiar contraseña
     */
    public function cambiarPassword(array $datos): array
    {
        try {
            // Verificar que hay sesión activa
            $usuario = $this->servicioAutenticacion->verificarSesion();
            if (!$usuario) {
                return [
                    'success' => false,
                    'error' => 'No hay sesión activa'
                ];
            }
            
            // Validar datos requeridos
            $errores = $this->validarDatosCambioPassword($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Cambiar contraseña
            $resultado = $this->servicioAutenticacion->cambiarPassword(
                $usuario['id'],
                $datos['password_actual'],
                $datos['password_nuevo']
            );
            
            return $resultado;
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Maneja la petición GET para verificar permisos
     */
    public function verificarPermiso(string $permiso): array
    {
        try {
            $tienePermiso = $this->servicioAutenticacion->tienePermiso($permiso);
            
            return [
                'success' => true,
                'data' => [
                    'permiso' => $permiso,
                    'tiene_permiso' => $tienePermiso
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Valida los datos de login
     */
    private function validarDatosLogin(array $datos): array
    {
        $errores = [];
        
        if (empty($datos['username'])) {
            $errores[] = 'El nombre de usuario es requerido';
        }
        
        if (empty($datos['password'])) {
            $errores[] = 'La contraseña es requerida';
        }
        
        return $errores;
    }

    /**
     * Valida los datos de cambio de contraseña
     */
    private function validarDatosCambioPassword(array $datos): array
    {
        $errores = [];
        
        if (empty($datos['password_actual'])) {
            $errores[] = 'La contraseña actual es requerida';
        }
        
        if (empty($datos['password_nuevo'])) {
            $errores[] = 'La nueva contraseña es requerida';
        }
        
        if (empty($datos['password_confirmacion'])) {
            $errores[] = 'La confirmación de contraseña es requerida';
        }
        
        if (!empty($datos['password_nuevo']) && !empty($datos['password_confirmacion'])) {
            if ($datos['password_nuevo'] !== $datos['password_confirmacion']) {
                $errores[] = 'Las contraseñas no coinciden';
            }
        }
        
        if (!empty($datos['password_nuevo']) && strlen($datos['password_nuevo']) < 6) {
            $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres';
        }
        
        return $errores;
    }
}
