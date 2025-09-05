<?php

namespace SistemaAdmin\Interfaces;

use SistemaAdmin\Models\Profesor;
use SistemaAdmin\Exceptions\ProfesorNoEncontradoException;

/**
 * TDA (Tipo de Dato Abstracto) - Interface ServicioProfesores
 * 
 * Define el contrato para el servicio de gestión de profesores.
 */
interface IServicioProfesores
{
    /**
     * Busca un profesor por su ID
     * 
     * @param int $id ID del profesor
     * @return Profesor|null El profesor encontrado o null si no existe
     * @throws ProfesorNoEncontradoException Si el profesor no existe
     */
    public function buscarPorId(int $id): ?Profesor;

    /**
     * Busca un profesor por su DNI
     * 
     * @param string $dni DNI del profesor
     * @return Profesor|null El profesor encontrado o null si no existe
     */
    public function buscarPorDni(string $dni): ?Profesor;

    /**
     * Obtiene todos los profesores activos
     * 
     * @return array<Profesor> Lista de profesores activos
     */
    public function obtenerTodos(): array;

    /**
     * Obtiene profesores por especialidad
     * 
     * @param string $especialidad Especialidad a buscar
     * @return array<Profesor> Lista de profesores de la especialidad
     */
    public function obtenerPorEspecialidad(string $especialidad): array;

    /**
     * Busca profesores por nombre o apellido
     * 
     * @param string $termino Término de búsqueda
     * @return array<Profesor> Lista de profesores que coinciden
     */
    public function buscarPorNombre(string $termino): array;

    /**
     * Crea un nuevo profesor
     * 
     * @param Profesor $profesor El profesor a crear
     * @return Profesor El profesor creado con ID asignado
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function crear(Profesor $profesor): Profesor;

    /**
     * Actualiza un profesor existente
     * 
     * @param Profesor $profesor El profesor a actualizar
     * @return Profesor El profesor actualizado
     * @throws ProfesorNoEncontradoException Si el profesor no existe
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function actualizar(Profesor $profesor): Profesor;

    /**
     * Elimina (desactiva) un profesor
     * 
     * @param int $id ID del profesor a eliminar
     * @return bool True si se eliminó correctamente
     * @throws ProfesorNoEncontradoException Si el profesor no existe
     */
    public function eliminar(int $id): bool;

    /**
     * Obtiene estadísticas de profesores
     * 
     * @return array Estadísticas (total, por especialidad, etc.)
     */
    public function obtenerEstadisticas(): array;

    /**
     * Verifica si un DNI ya está en uso
     * 
     * @param string $dni DNI a verificar
     * @param int|null $excluirId ID del profesor a excluir de la verificación
     * @return bool True si el DNI ya está en uso
     */
    public function dniExiste(string $dni, ?int $excluirId = null): bool;

    /**
     * Obtiene profesores que pueden dictar una materia específica
     * 
     * @param int $materiaId ID de la materia
     * @return array<Profesor> Lista de profesores disponibles
     */
    public function obtenerPorMateria(int $materiaId): array;
}
