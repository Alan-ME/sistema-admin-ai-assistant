<?php

namespace SistemaAdmin\Interfaces;

use SistemaAdmin\Models\Estudiante;
use SistemaAdmin\Exceptions\EstudianteNoEncontradoException;

/**
 * TDA (Tipo de Dato Abstracto) - Interface ServicioEstudiantes
 * 
 * Define el contrato para el servicio de gestión de estudiantes.
 * Especifica qué operaciones se pueden realizar sin importar cómo se implementan.
 */
interface IServicioEstudiantes
{
    /**
     * Busca un estudiante por su ID
     * 
     * @param int $id ID del estudiante
     * @return Estudiante|null El estudiante encontrado o null si no existe
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function buscarPorId(int $id): ?Estudiante;

    /**
     * Busca un estudiante por su DNI
     * 
     * @param string $dni DNI del estudiante
     * @return Estudiante|null El estudiante encontrado o null si no existe
     */
    public function buscarPorDni(string $dni): ?Estudiante;

    /**
     * Obtiene todos los estudiantes activos
     * 
     * @return array<Estudiante> Lista de estudiantes activos
     */
    public function obtenerTodos(): array;

    /**
     * Obtiene estudiantes por curso
     * 
     * @param int $cursoId ID del curso
     * @return array<Estudiante> Lista de estudiantes del curso
     */
    public function obtenerPorCurso(int $cursoId): array;

    /**
     * Busca estudiantes por nombre o apellido
     * 
     * @param string $termino Término de búsqueda
     * @return array<Estudiante> Lista de estudiantes que coinciden
     */
    public function buscarPorNombre(string $termino): array;

    /**
     * Crea un nuevo estudiante
     * 
     * @param Estudiante $estudiante El estudiante a crear
     * @return Estudiante El estudiante creado con ID asignado
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function crear(Estudiante $estudiante): Estudiante;

    /**
     * Actualiza un estudiante existente
     * 
     * @param Estudiante $estudiante El estudiante a actualizar
     * @return Estudiante El estudiante actualizado
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function actualizar(Estudiante $estudiante): Estudiante;

    /**
     * Elimina (desactiva) un estudiante
     * 
     * @param int $id ID del estudiante a eliminar
     * @return bool True si se eliminó correctamente
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function eliminar(int $id): bool;

    /**
     * Obtiene estadísticas de estudiantes
     * 
     * @return array Estadísticas (total, por curso, por turno, etc.)
     */
    public function obtenerEstadisticas(): array;

    /**
     * Obtiene estudiantes con cumpleaños en una fecha específica
     * 
     * @param \DateTime $fecha Fecha a consultar
     * @return array<Estudiante> Lista de estudiantes que cumplen años
     */
    public function obtenerCumpleaneros(\DateTime $fecha): array;

    /**
     * Verifica si un DNI ya está en uso
     * 
     * @param string $dni DNI a verificar
     * @param int|null $excluirId ID del estudiante a excluir de la verificación
     * @return bool True si el DNI ya está en uso
     */
    public function dniExiste(string $dni, ?int $excluirId = null): bool;
}
