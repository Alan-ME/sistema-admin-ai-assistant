<?php

namespace SistemaAdmin\Interfaces;

use SistemaAdmin\Models\Materia;

/**
 * TDA (Tipo de Dato Abstracto) - Interface ServicioMaterias
 * 
 * Define el contrato para el servicio de gestión de materias.
 */
interface IServicioMaterias
{
    /**
     * Busca una materia por su ID
     * 
     * @param int $id ID de la materia
     * @return Materia|null La materia encontrada o null si no existe
     */
    public function buscarPorId(int $id): ?Materia;

    /**
     * Busca una materia por su código
     * 
     * @param string $codigo Código de la materia
     * @return Materia|null La materia encontrada o null si no existe
     */
    public function buscarPorCodigo(string $codigo): ?Materia;

    /**
     * Obtiene todas las materias activas
     * 
     * @return array<Materia> Lista de materias activas
     */
    public function obtenerTodas(): array;

    /**
     * Obtiene materias por especialidad
     * 
     * @param int $especialidadId ID de la especialidad
     * @return array<Materia> Lista de materias de la especialidad
     */
    public function obtenerPorEspecialidad(int $especialidadId): array;

    /**
     * Obtiene materias comunes (sin especialidad)
     * 
     * @return array<Materia> Lista de materias comunes
     */
    public function obtenerComunes(): array;

    /**
     * Busca materias por nombre
     * 
     * @param string $termino Término de búsqueda
     * @return array<Materia> Lista de materias que coinciden
     */
    public function buscarPorNombre(string $termino): array;

    /**
     * Crea una nueva materia
     * 
     * @param Materia $materia La materia a crear
     * @return Materia La materia creada con ID asignado
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function crear(Materia $materia): Materia;

    /**
     * Actualiza una materia existente
     * 
     * @param Materia $materia La materia a actualizar
     * @return Materia La materia actualizada
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function actualizar(Materia $materia): Materia;

    /**
     * Elimina (desactiva) una materia
     * 
     * @param int $id ID de la materia a eliminar
     * @return bool True si se eliminó correctamente
     */
    public function eliminar(int $id): bool;

    /**
     * Obtiene estadísticas de materias
     * 
     * @return array Estadísticas (total, por especialidad, carga horaria, etc.)
     */
    public function obtenerEstadisticas(): array;

    /**
     * Verifica si un código ya está en uso
     * 
     * @param string $codigo Código a verificar
     * @param int|null $excluirId ID de la materia a excluir de la verificación
     * @return bool True si el código ya está en uso
     */
    public function codigoExiste(string $codigo, ?int $excluirId = null): bool;

    /**
     * Obtiene materias por carga horaria
     * 
     * @param int $horasMinimas Horas mínimas
     * @param int $horasMaximas Horas máximas
     * @return array<Materia> Lista de materias en el rango
     */
    public function obtenerPorCargaHoraria(int $horasMinimas, int $horasMaximas): array;
}
