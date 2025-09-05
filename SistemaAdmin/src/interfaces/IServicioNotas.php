<?php

namespace SistemaAdmin\Interfaces;

use SistemaAdmin\Models\Nota;
use SistemaAdmin\Exceptions\EstudianteNoEncontradoException;
use SistemaAdmin\Exceptions\CalificacionInvalidaException;

/**
 * TDA (Tipo de Dato Abstracto) - Interface ServicioNotas
 * 
 * Define el contrato para el servicio de gestión de calificaciones.
 * Siguiendo el ejemplo del documento de análisis.
 */
interface IServicioNotas
{
    /**
     * Carga una nueva nota para un estudiante en una materia
     * 
     * @param int $idEstudiante ID del estudiante
     * @param int $idMateria ID de la materia
     * @param float $calificacion Valor de la calificación (0-10)
     * @param string $bimestre Bimestre (1, 2, 3, 4)
     * @param string|null $observaciones Observaciones opcionales
     * @return Nota La nota creada
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     * @throws CalificacionInvalidaException Si la calificación es inválida
     * @throws \InvalidArgumentException Si otros parámetros son inválidos
     */
    public function cargarNota(
        int $idEstudiante, 
        int $idMateria, 
        float $calificacion, 
        string $bimestre,
        ?string $observaciones = null
    ): Nota;

    /**
     * Obtiene el promedio de un estudiante en una materia específica
     * 
     * @param int $idEstudiante ID del estudiante
     * @param int $idMateria ID de la materia
     * @return float Promedio de la materia
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function obtenerPromedioMateria(int $idEstudiante, int $idMateria): float;

    /**
     * Obtiene el promedio general de un estudiante
     * 
     * @param int $idEstudiante ID del estudiante
     * @return float Promedio general del estudiante
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function obtenerPromedioEstudiante(int $idEstudiante): float;

    /**
     * Obtiene todas las notas de un estudiante
     * 
     * @param int $idEstudiante ID del estudiante
     * @return array<Nota> Lista de notas del estudiante
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function obtenerNotasEstudiante(int $idEstudiante): array;

    /**
     * Obtiene notas de un estudiante por materia
     * 
     * @param int $idEstudiante ID del estudiante
     * @param int $idMateria ID de la materia
     * @return array<Nota> Lista de notas de la materia
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function obtenerNotasMateria(int $idEstudiante, int $idMateria): array;

    /**
     * Obtiene notas por bimestre
     * 
     * @param int $idEstudiante ID del estudiante
     * @param string $bimestre Bimestre (1, 2, 3, 4)
     * @return array<Nota> Lista de notas del bimestre
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function obtenerNotasBimestre(int $idEstudiante, string $bimestre): array;

    /**
     * Actualiza una nota existente
     * 
     * @param int $idNota ID de la nota
     * @param float $nuevoValor Nuevo valor de la calificación
     * @param string|null $observaciones Nuevas observaciones
     * @return Nota La nota actualizada
     * @throws CalificacionInvalidaException Si la calificación es inválida
     * @throws \InvalidArgumentException Si la nota no existe
     */
    public function actualizarNota(int $idNota, float $nuevoValor, ?string $observaciones = null): Nota;

    /**
     * Elimina una nota
     * 
     * @param int $idNota ID de la nota a eliminar
     * @return bool True si se eliminó correctamente
     * @throws \InvalidArgumentException Si la nota no existe
     */
    public function eliminarNota(int $idNota): bool;

    /**
     * Obtiene estadísticas de notas
     * 
     * @param int|null $idMateria ID de materia específica (opcional)
     * @param string|null $bimestre Bimestre específico (opcional)
     * @return array Estadísticas (promedio general, cantidad de notas, etc.)
     */
    public function obtenerEstadisticas(?int $idMateria = null, ?string $bimestre = null): array;

    /**
     * Verifica si un estudiante está aprobado en una materia
     * 
     * @param int $idEstudiante ID del estudiante
     * @param int $idMateria ID de la materia
     * @return bool True si está aprobado
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function estaAprobado(int $idEstudiante, int $idMateria): bool;

    /**
     * Obtiene el boletín de un estudiante
     * 
     * @param int $idEstudiante ID del estudiante
     * @return array Datos del boletín (notas por materia, promedios, etc.)
     * @throws EstudianteNoEncontradoException Si el estudiante no existe
     */
    public function obtenerBoletin(int $idEstudiante): array;
}
