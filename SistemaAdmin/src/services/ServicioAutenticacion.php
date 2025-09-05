<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Interfaces\IServicioAutenticacion;

/**
 * Servicio de Autenticación (Refactorizado)
 * 
 * Ahora solo maneja la lógica de autenticación, delegando
 * responsabilidades específicas a servicios especializados.
 * Implementa la interfaz IServicioAutenticacion.
 */
class ServicioAutenticacion implements IServicioAutenticacion
{
    private UsuarioRepository $usuarioRepository;
    private SessionService $sessionService;
    private PermissionService $permissionService;
    private AuthCacheService $authCacheService;

    public function __construct($database)
    {
        $this->usuarioRepository = new UsuarioRepository($database);
        $this->sessionService = new SessionService();
        $this->permissionService = new PermissionService($this->sessionService);
        $this->authCacheService = new AuthCacheService($database);
    }

    public function autenticar(string $username, string $password): array
    {
        try {
            // Buscar usuario usando cache
            $usuario = $this->authCacheService->getUserByUsername($username);
            
            if (!$usuario) {
                return [
                    'success' => false,
                    'error' => 'Usuario o contraseña incorrectos'
                ];
            }
            
            // Verificar contraseña
            if (!password_verify($password, $usuario['password'])) {
                return [
                    'success' => false,
                    'error' => 'Usuario o contraseña incorrectos'
                ];
            }
            
            // Usuario autenticado exitosamente
            return [
                'success' => true,
                'data' => [
                    'id' => $usuario['id'],
                    'username' => $usuario['username'],
                    'nombre' => $usuario['nombre'],
                    'apellido' => $usuario['apellido'],
                    'email' => $usuario['email'] ?? '',
                    'rol' => $usuario['rol'],
                    'ultimo_acceso' => $usuario['ultimo_acceso'] ?? null
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    public function actualizarUltimoAcceso(int $usuarioId): bool
    {
        return $this->usuarioRepository->actualizarUltimoAcceso($usuarioId);
    }

    public function obtenerUsuarioPorId(int $usuarioId): ?array
    {
        return $this->authCacheService->getUserById($usuarioId);
    }

    public function cambiarPassword(int $usuarioId, string $passwordActual, string $passwordNuevo): array
    {
        try {
            // Verificar contraseña actual usando el repositorio
            if (!$this->usuarioRepository->verificarPassword($usuarioId, $passwordActual)) {
                return [
                    'success' => false,
                    'error' => 'La contraseña actual es incorrecta'
                ];
            }
            
            // Actualizar contraseña usando el repositorio
            $passwordHash = password_hash($passwordNuevo, PASSWORD_DEFAULT);
            if ($this->usuarioRepository->cambiarPassword($usuarioId, $passwordHash)) {
                return [
                    'success' => true,
                    'message' => 'Contraseña actualizada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Error al actualizar la contraseña'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error interno del servidor'
            ];
        }
    }

    public function cerrarSesion(): bool
    {
        try {
            $this->sessionService->cerrar();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function verificarSesion(): ?array
    {
        if (!$this->sessionService->tieneSesion()) {
            return null;
        }
        
        $usuarioId = $this->sessionService->obtenerUsuarioId();
        return $this->obtenerUsuarioPorId($usuarioId);
    }

    public function tienePermiso(string $permiso): bool
    {
        return $this->permissionService->tienePermiso($permiso);
    }
}
