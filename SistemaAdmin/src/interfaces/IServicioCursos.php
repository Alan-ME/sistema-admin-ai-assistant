<?php

namespace SistemaAdmin\Interfaces;

use SistemaAdmin\Models\Curso;
use SistemaAdmin\Exceptions\CursoNoEncontradoException;

/**
 * TDA (Tipo de Dato Abstracto) - Interface ServicioCursos
 * 
 * Define el contrato para el servicio de gestión de cursos.
 */
interface IServicioCursos
{
    /**
     * Busca un curso por su ID
     * 
     * @param int $id ID del curso
     * @return Curso|null El curso encontrado o null si no existe
     * @throws CursoNoEncontradoException Si el curso no existe
     */
    public function buscarPorId(int $id): ?Curso;

    /**
     * Obtiene todos los cursos activos
     * 
     * @return array<Curso> Lista de cursos activos
     */
    public function obtenerTodos(): array;

    /**
     * Obtiene cursos por año
     * 
     * @param int $anio Año a buscar
     * @return array<Curso> Lista de cursos del año
     */
    public function obtenerPorAnio(int $anio): array;

    /**
     * Obtiene cursos por especialidad
     * 
     * @param int $especialidadId ID de la especialidad
     * @return array<Curso> Lista de cursos de la especialidad
     */
    public function obtenerPorEspecialidad(int $especialidadId): array;

    /**
     * Obtiene cursos por turno
     * 
     * @param int $turnoId ID del turno
     * @return array<Curso> Lista de cursos del turno
     */
    public function obtenerPorTurno(int $turnoId): array;

    /**
     * Crea un nuevo curso
     * 
     * @param Curso $curso El curso a crear
     * @return Curso El curso creado con ID asignado
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function crear(Curso $curso): Curso;

    /**
     * Actualiza un curso existente
     * 
     * @param Curso $curso El curso a actualizar
     * @return Curso El curso actualizado
     * @throws CursoNoEncontradoException Si el curso no existe
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function actualizar(Curso $curso): Curso;

    /**
     * Elimina (desactiva) un curso
     * 
     * @param int $id ID del curso a eliminar
     * @return bool True si se eliminó correctamente
     * @throws CursoNoEncontradoException Si el curso no existe
     */
    public function eliminar(int $id): bool;

    /**
     * Obtiene estadísticas de cursos
     * 
     * @return array Estadísticas (total, por año, por especialidad, etc.)
     */
    public function obtenerEstadisticas(): array;

    /**
     * Obtiene cursos del ciclo básico (1° a 3°)
     * 
     * @return array<Curso> Lista de cursos del ciclo básico
     */
    public function obtenerCicloBasico(): array;

    /**
     * Obtiene cursos del ciclo superior (4° a 7°)
     * 
     * @return array<Curso> Lista de cursos del ciclo superior
     */
    public function obtenerCicloSuperior(): array;

    /**
     * Verifica si existe un curso con el mismo año y división
     * 
     * @param int $anio Año del curso
     * @param string $division División del curso
     * @param int|null $excluirId ID del curso a excluir de la verificación
     * @return bool True si ya existe
     */
    public function existeCurso(int $anio, string $division, ?int $excluirId = null): bool;
}
