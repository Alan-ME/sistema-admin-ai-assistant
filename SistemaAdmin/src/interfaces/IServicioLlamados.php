<?php

namespace SistemaAdmin\Interfaces;

use SistemaAdmin\Models\LlamadoAtencion;
use SistemaAdmin\Exceptions\EstudianteNoEncontradoException;

/**
 * TDA (Tipo de Dato Abstracto) - Interface ServicioLlamados
 * 
 * Define el contrato para el servicio de gestión de llamados de atención.
 */
interface IServicioLlamados
{
    /**
     * Registra un nuevo llamado de atención
     * 
     * @param int $estudianteId ID del estudiante
     * @param string $motivo Motivo del llamado
     * @param string $descripcion Descripción detallada
     * @param int $usuarioId ID del usuario que registra el llamado
     * @param string|null $sancion Sanción aplicada (opcional)
     * @return LlamadoAtencion El llamado creado
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function registrarLlamado(
        int $estudianteId,
        string $motivo,
        string $descripcion,
        int $usuarioId,
        ?string $sancion = null
    ): LlamadoAtencion;

    /**
     * Obtiene todos los llamados de un estudiante
     * 
     * @param int $estudianteId ID del estudiante
     * @return array<LlamadoAtencion> Lista de llamados del estudiante
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function obtenerPorEstudiante(int $estudianteId): array;

    /**
     * Obtiene llamados recientes (últimos N días)
     * 
     * @param int $dias Número de días hacia atrás
     * @return array<LlamadoAtencion> Lista de llamados recientes
     */
    public function obtenerRecientes(int $dias = 7): array;

    /**
     * Obtiene llamados por período
     * 
     * @param \DateTime $fechaInicio Fecha de inicio
     * @param \DateTime $fechaFin Fecha de fin
     * @return array<LlamadoAtencion> Lista de llamados del período
     */
    public function obtenerPorPeriodo(\DateTime $fechaInicio, \DateTime $fechaFin): array;

    /**
     * Obtiene llamados graves
     * 
     * @return array<LlamadoAtencion> Lista de llamados clasificados como graves
     */
    public function obtenerGraves(): array;

    /**
     * Actualiza la sanción de un llamado
     * 
     * @param int $llamadoId ID del llamado
     * @param string $sancion Nueva sanción
     * @return LlamadoAtencion El llamado actualizado
     * @throws \InvalidArgumentException Si el llamado no existe
     */
    public function actualizarSancion(int $llamadoId, string $sancion): LlamadoAtencion;

    /**
     * Elimina un llamado
     * 
     * @param int $llamadoId ID del llamado a eliminar
     * @return bool True si se eliminó correctamente
     * @throws \InvalidArgumentException Si el llamado no existe
     */
    public function eliminarLlamado(int $llamadoId): bool;

    /**
     * Obtiene estadísticas de llamados
     * 
     * @param int|null $estudianteId ID del estudiante específico (opcional)
     * @param \DateTime|null $fechaInicio Fecha de inicio (opcional)
     * @param \DateTime|null $fechaFin Fecha de fin (opcional)
     * @return array Estadísticas (total, graves, por motivo, etc.)
     */
    public function obtenerEstadisticas(
        ?int $estudianteId = null,
        ?\DateTime $fechaInicio = null,
        ?\DateTime $fechaFin = null
    ): array;

    /**
     * Obtiene el historial disciplinario de un estudiante
     * 
     * @param int $estudianteId ID del estudiante
     * @return array Datos del historial (llamados, sanciones, tendencias)
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function obtenerHistorialDisciplinario(int $estudianteId): array;

    /**
     * Verifica si un estudiante tiene llamados recientes
     * 
     * @param int $estudianteId ID del estudiante
     * @param int $dias Número de días a considerar
     * @return bool True si tiene llamados recientes
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function tieneLlamadosRecientes(int $estudianteId, int $dias = 30): bool;
}
