<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Services\ServicioProfesores;
use SistemaAdmin\Services\ServicioLlamados;
use SistemaAdmin\Services\ServicioAutenticacion;

/**
 * Controller para manejar las peticiones HTTP relacionadas con el dashboard
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para el panel de control.
 */
class DashboardController
{
    private ServicioEstudiantes $servicioEstudiantes;
    private ServicioProfesores $servicioProfesores;
    private ServicioLlamados $servicioLlamados;
    private ServicioAutenticacion $servicioAutenticacion;
    private $database;

    public function __construct(
        ServicioEstudiantes $servicioEstudiantes,
        ServicioProfesores $servicioProfesores,
        ServicioLlamados $servicioLlamados,
        ServicioAutenticacion $servicioAutenticacion,
        $database
    ) {
        $this->servicioEstudiantes = $servicioEstudiantes;
        $this->servicioProfesores = $servicioProfesores;
        $this->servicioLlamados = $servicioLlamados;
        $this->servicioAutenticacion = $servicioAutenticacion;
        $this->database = $database;
    }

    /**
     * Maneja la petición GET para obtener estadísticas del dashboard
     */
    public function obtenerEstadisticas(): array
    {
        try {
            // Obtener estadísticas de estudiantes
            $estadisticasEstudiantes = $this->servicioEstudiantes->obtenerEstadisticas();
            
            // Obtener estadísticas de profesores
            $estadisticasProfesores = $this->servicioProfesores->obtenerEstadisticas();
            
            // Obtener estadísticas de llamados
            $estadisticasLlamados = $this->servicioLlamados->obtenerEstadisticas();
            
            // Obtener estadísticas adicionales de la base de datos
            $estadisticasAdicionales = $this->obtenerEstadisticasAdicionales();
            
            return [
                'success' => true,
                'data' => [
                    'estudiantes' => $estadisticasEstudiantes,
                    'profesores' => $estadisticasProfesores,
                    'llamados' => $estadisticasLlamados,
                    'adicionales' => $estadisticasAdicionales
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener cumpleañeros del día
     */
    public function obtenerCumpleaneros(): array
    {
        try {
            $cumpleaneros = $this->database->fetchAll("
                SELECT e.apellido, e.nombre, c.anio, c.division, esp.nombre as especialidad,
                       YEAR(CURDATE()) - YEAR(e.fecha_nacimiento) as edad
                FROM estudiantes e
                LEFT JOIN cursos c ON e.curso_id = c.id
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                WHERE e.activo = 1 
                AND DATE_FORMAT(e.fecha_nacimiento, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
                ORDER BY e.apellido, e.nombre
            ");
            
            return [
                'success' => true,
                'data' => $cumpleaneros,
                'total' => count($cumpleaneros)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener últimos llamados de atención
     */
    public function obtenerUltimosLlamados(int $limite = 10): array
    {
        try {
            $llamados = $this->database->fetchAll("
                SELECT l.*, e.apellido, e.nombre, e.dni, c.anio, c.division, esp.nombre as especialidad
                FROM llamados_atencion l
                JOIN estudiantes e ON l.estudiante_id = e.id
                LEFT JOIN cursos c ON e.curso_id = c.id
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                WHERE l.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                ORDER BY l.fecha DESC, l.id DESC
                LIMIT ?
            ", [$limite]);
            
            return [
                'success' => true,
                'data' => $llamados,
                'total' => count($llamados)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener estudiantes por turno
     */
    public function obtenerEstudiantesPorTurno(): array
    {
        try {
            $estudiantesPorTurno = $this->database->fetchAll("
                SELECT t.nombre as turno, COUNT(e.id) as cantidad
                FROM turnos t
                LEFT JOIN cursos c ON t.id = c.turno_id AND c.activo = 1
                LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
                GROUP BY t.id, t.nombre
                ORDER BY t.id
            ");
            
            return [
                'success' => true,
                'data' => $estudiantesPorTurno
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener resumen del dashboard
     */
    public function obtenerResumen(): array
    {
        try {
            // Obtener todas las estadísticas en paralelo
            $estadisticas = $this->obtenerEstadisticas();
            $cumpleaneros = $this->obtenerCumpleaneros();
            $ultimosLlamados = $this->obtenerUltimosLlamados();
            $estudiantesPorTurno = $this->obtenerEstudiantesPorTurno();
            
            return [
                'success' => true,
                'data' => [
                    'estadisticas' => $estadisticas['data'] ?? [],
                    'cumpleaneros' => $cumpleaneros['data'] ?? [],
                    'ultimos_llamados' => $ultimosLlamados['data'] ?? [],
                    'estudiantes_por_turno' => $estudiantesPorTurno['data'] ?? []
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene estadísticas adicionales de la base de datos
     */
    private function obtenerEstadisticasAdicionales(): array
    {
        try {
            // Total cursos activos
            $totalCursos = $this->database->fetch("SELECT COUNT(*) as total FROM cursos WHERE activo = 1")['total'];
            
            // Cumpleaños de hoy
            $cumpleanosHoy = $this->database->fetch("
                SELECT COUNT(*) as total 
                FROM estudiantes 
                WHERE activo = 1 
                AND DATE_FORMAT(fecha_nacimiento, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
            ")['total'] ?? 0;
            
            return [
                'total_cursos' => $totalCursos,
                'cumpleanos_hoy' => $cumpleanosHoy
            ];
            
        } catch (\Exception $e) {
            return [
                'total_cursos' => 0,
                'cumpleanos_hoy' => 0
            ];
        }
    }
}
