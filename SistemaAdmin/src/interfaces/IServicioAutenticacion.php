<?php

namespace SistemaAdmin\Interfaces;

/**
 * Interfaz TDA para el servicio de autenticación
 * 
 * Define el contrato para la lógica de negocio relacionada con
 * la autenticación y autorización de usuarios.
 */
interface IServicioAutenticacion
{
    /**
     * Autentica un usuario con username y password
     * 
     * @param string $username Nombre de usuario
     * @param string $password Contraseña
     * @return array Resultado de la autenticación
     */
    public function autenticar(string $username, string $password): array;

    /**
     * Actualiza el último acceso del usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return bool True si se actualizó correctamente
     */
    public function actualizarUltimoAcceso(int $usuarioId): bool;

    /**
     * Obtiene un usuario por su ID
     * 
     * @param int $usuarioId ID del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function obtenerUsuarioPorId(int $usuarioId): ?array;

    /**
     * Cambia la contraseña de un usuario
     * 
     * @param int $usuarioId ID del usuario
     * @param string $passwordActual Contraseña actual
     * @param string $passwordNuevo Nueva contraseña
     * @return array Resultado de la operación
     */
    public function cambiarPassword(int $usuarioId, string $passwordActual, string $passwordNuevo): array;

    /**
     * Cierra la sesión del usuario actual
     * 
     * @return bool True si se cerró correctamente
     */
    public function cerrarSesion(): bool;

    /**
     * Verifica si hay una sesión activa
     * 
     * @return array|null Datos del usuario o null si no hay sesión
     */
    public function verificarSesion(): ?array;

    /**
     * Verifica si el usuario actual tiene un permiso específico
     * 
     * @param string $permiso Permiso a verificar
     * @return bool True si tiene el permiso
     */
    public function tienePermiso(string $permiso): bool;
}
