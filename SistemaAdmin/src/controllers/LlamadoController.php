<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioLlamados;
use SistemaAdmin\Services\ServicioEstudiantes;
use DateTime;

/**
 * Controller para manejar las peticiones HTTP relacionadas con llamados de atención
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para llamados de atención.
 */
class LlamadoController
{
    private ServicioLlamados $servicioLlamados;
    private ServicioEstudiantes $servicioEstudiantes;

    public function __construct(ServicioLlamados $servicioLlamados, ServicioEstudiantes $servicioEstudiantes)
    {
        $this->servicioLlamados = $servicioLlamados;
        $this->servicioEstudiantes = $servicioEstudiantes;
    }

    /**
     * Maneja la petición POST para registrar un nuevo llamado
     */
    public function registrar(array $datos): array
    {
        try {
            // Validar datos requeridos
            $errores = $this->validarDatosRegistro($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Registrar el llamado
            $llamado = $this->servicioLlamados->registrarLlamado(
                $datos['estudiante_id'],
                $datos['motivo'],
                $datos['descripcion'],
                $datos['usuario_id'],
                $datos['sancion'] ?? null
            );
            
            return [
                'success' => true,
                'data' => $llamado->toArray(),
                'message' => 'Llamado registrado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener llamados de un estudiante
     */
    public function obtenerPorEstudiante(int $estudianteId): array
    {
        try {
            $llamados = $this->servicioLlamados->obtenerPorEstudiante($estudianteId);
            
            return [
                'success' => true,
                'data' => array_map(fn($llamado) => $llamado->toArray(), $llamados),
                'total' => count($llamados)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener llamados recientes
     */
    public function recientes(int $dias = 7): array
    {
        try {
            $llamados = $this->servicioLlamados->obtenerRecientes($dias);
            
            return [
                'success' => true,
                'data' => $llamados, // Ya son arrays, no necesitamos convertir
                'total' => count($llamados)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener llamados graves
     */
    public function graves(): array
    {
        try {
            $llamados = $this->servicioLlamados->obtenerGraves();
            
            return [
                'success' => true,
                'data' => array_map(fn($llamado) => $llamado->toArray(), $llamados),
                'total' => count($llamados)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener llamados por período
     */
    public function porPeriodo(string $fechaInicio, string $fechaFin): array
    {
        try {
            $fechaInicioObj = new DateTime($fechaInicio);
            $fechaFinObj = new DateTime($fechaFin);
            
            $llamados = $this->servicioLlamados->obtenerPorPeriodo($fechaInicioObj, $fechaFinObj);
            
            return [
                'success' => true,
                'data' => array_map(fn($llamado) => $llamado->toArray(), $llamados),
                'total' => count($llamados)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición PUT para aplicar una sanción
     */
    public function aplicarSancion(int $llamadoId, array $datos): array
    {
        try {
            if (empty($datos['sancion'])) {
                return [
                    'success' => false,
                    'error' => 'La sanción es requerida'
                ];
            }
            
            $llamado = $this->servicioLlamados->actualizarSancion($llamadoId, $datos['sancion']);
            
            return [
                'success' => true,
                'data' => $llamado->toArray(),
                'message' => 'Sanción aplicada exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición DELETE para eliminar un llamado
     */
    public function eliminar(int $llamadoId): array
    {
        try {
            $this->servicioLlamados->eliminarLlamado($llamadoId);
            
            return [
                'success' => true,
                'message' => 'Llamado eliminado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener estadísticas de llamados
     */
    public function estadisticas(?int $estudianteId = null, ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        try {
            $fechaInicioObj = $fechaInicio ? new DateTime($fechaInicio) : null;
            $fechaFinObj = $fechaFin ? new DateTime($fechaFin) : null;
            
            $estadisticas = $this->servicioLlamados->obtenerEstadisticas($estudianteId, $fechaInicioObj, $fechaFinObj);
            
            return [
                'success' => true,
                'data' => $estadisticas
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener estudiantes problemáticos
     */
    public function estudiantesProblematicos(): array
    {
        try {
            $problematicos = $this->servicioLlamados->obtenerEstudiantesProblematicos();
            
            return [
                'success' => true,
                'data' => $problematicos,
                'total' => count($problematicos)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener resumen mensual
     */
    public function resumenMensual(int $mes, int $anio): array
    {
        try {
            $resumen = $this->servicioLlamados->obtenerResumenMensual($mes, $anio);
            
            return [
                'success' => true,
                'data' => $resumen
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para verificar si un estudiante tiene llamados recientes
     */
    public function tieneLlamadosRecientes(int $estudianteId, int $dias = 30): array
    {
        try {
            $tieneLlamados = $this->servicioLlamados->tieneLlamadosRecientes($estudianteId, $dias);
            
            return [
                'success' => true,
                'data' => [
                    'estudiante_id' => $estudianteId,
                    'tiene_llamados_recientes' => $tieneLlamados,
                    'dias' => $dias
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para verificar si un estudiante es problemático
     */
    public function esEstudianteProblematico(int $estudianteId): array
    {
        try {
            $esProblematico = $this->servicioLlamados->esEstudianteProblematico($estudianteId);
            
            return [
                'success' => true,
                'data' => [
                    'estudiante_id' => $estudianteId,
                    'es_problematico' => $esProblematico
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida los datos para registrar un llamado
     */
    private function validarDatosRegistro(array $datos): array
    {
        $errores = [];
        
        if (empty($datos['estudiante_id'])) {
            $errores[] = 'El ID del estudiante es requerido';
        }
        
        if (empty($datos['motivo'])) {
            $errores[] = 'El motivo es requerido';
        }
        
        if (empty($datos['descripcion'])) {
            $errores[] = 'La descripción es requerida';
        }
        
        if (empty($datos['usuario_id'])) {
            $errores[] = 'El ID del usuario es requerido';
        }
        
        return $errores;
    }
}
