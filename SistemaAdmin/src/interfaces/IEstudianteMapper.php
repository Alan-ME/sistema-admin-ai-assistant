<?php

namespace SistemaAdmin\Interfaces;

use SistemaAdmin\Models\Estudiante;

/**
 * TDA (Tipo de Dato Abstracto) - Interface EstudianteMapper
 * 
 * Define el contrato para el mapper de persistencia de estudiantes.
 * Abstrae el acceso a la base de datos siguiendo el patrón Data Mapper.
 */
interface IEstudianteMapper
{
    /**
     * Busca un estudiante por ID en la base de datos
     * 
     * @param int $id ID del estudiante
     * @return Estudiante|null El estudiante encontrado o null
     */
    public function findById(int $id): ?Estudiante;

    /**
     * Busca un estudiante por DNI en la base de datos
     * 
     * @param string $dni DNI del estudiante
     * @return Estudiante|null El estudiante encontrado o null
     */
    public function findByDni(string $dni): ?Estudiante;

    /**
     * Obtiene todos los estudiantes de la base de datos
     * 
     * @return array<Estudiante> Lista de todos los estudiantes
     */
    public function findAll(): array;

    /**
     * Obtiene estudiantes activos de la base de datos
     * 
     * @return array<Estudiante> Lista de estudiantes activos
     */
    public function findActive(): array;

    /**
     * Busca estudiantes por criterios
     * 
     * @param array $criteria Criterios de búsqueda
     * @return array<Estudiante> Lista de estudiantes que coinciden
     */
    public function findBy(array $criteria): array;

    /**
     * Guarda un estudiante en la base de datos
     * 
     * @param Estudiante $estudiante El estudiante a guardar
     * @return Estudiante El estudiante guardado con ID asignado
     */
    public function save(Estudiante $estudiante): Estudiante;

    /**
     * Actualiza un estudiante en la base de datos
     * 
     * @param Estudiante $estudiante El estudiante a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function update(Estudiante $estudiante): bool;

    /**
     * Elimina un estudiante de la base de datos
     * 
     * @param int $id ID del estudiante a eliminar
     * @return bool True si se eliminó correctamente
     */
    public function delete(int $id): bool;

    /**
     * Verifica si existe un estudiante con el DNI dado
     * 
     * @param string $dni DNI a verificar
     * @param int|null $excludeId ID del estudiante a excluir
     * @return bool True si existe
     */
    public function existsByDni(string $dni, ?int $excludeId = null): bool;

    /**
     * Obtiene el conteo de estudiantes por criterios
     * 
     * @param array $criteria Criterios de conteo
     * @return int Número de estudiantes que coinciden
     */
    public function countBy(array $criteria): int;
}
