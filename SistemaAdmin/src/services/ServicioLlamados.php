<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Interfaces\IServicioLlamados;
use SistemaAdmin\Models\LlamadoAtencion;
use SistemaAdmin\Mappers\LlamadoMapper;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Exceptions\EstudianteNoEncontradoException;
use DateTime;

/**
 * Implementación concreta del ServicioLlamados
 * 
 * Contiene la lógica de negocio para la gestión de llamados de atención.
 * Implementa la interfaz IServicioLlamados.
 */
class ServicioLlamados implements IServicioLlamados
{
    private LlamadoMapper $llamadoMapper;
    private EstudianteMapper $estudianteMapper;

    public function __construct(LlamadoMapper $llamadoMapper, EstudianteMapper $estudianteMapper)
    {
        $this->llamadoMapper = $llamadoMapper;
        $this->estudianteMapper = $estudianteMapper;
    }

    public function registrarLlamado(
        int $estudianteId,
        string $motivo,
        string $descripcion,
        int $usuarioId,
        ?string $sancion = null
    ): LlamadoAtencion {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($estudianteId);
        }
        
        // Validar que el motivo no esté vacío
        if (empty(trim($motivo))) {
            throw new \InvalidArgumentException("El motivo del llamado no puede estar vacío");
        }
        
        // Validar que la descripción no esté vacía
        if (empty(trim($descripcion))) {
            throw new \InvalidArgumentException("La descripción del llamado no puede estar vacía");
        }
        
        // Crear el llamado
        $llamado = new LlamadoAtencion($estudianteId, $motivo, $descripcion, $usuarioId, $sancion);
        
        // Guardar en la base de datos
        return $this->llamadoMapper->save($llamado);
    }

    public function obtenerPorEstudiante(int $estudianteId): array
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($estudianteId);
        }
        
        return $this->llamadoMapper->findByEstudiante($estudianteId);
    }

    public function obtenerRecientes(int $dias = 7): array
    {
        return $this->llamadoMapper->findRecientes($dias);
    }

    public function obtenerGraves(): array
    {
        return $this->llamadoMapper->findGraves();
    }

    public function obtenerPorPeriodo(DateTime $fechaInicio, DateTime $fechaFin): array
    {
        return $this->llamadoMapper->findByPeriodo($fechaInicio, $fechaFin);
    }

    public function actualizarSancion(int $llamadoId, string $sancion): LlamadoAtencion
    {
        // Buscar el llamado
        $llamado = $this->llamadoMapper->findById($llamadoId);
        if ($llamado === null) {
            throw new \InvalidArgumentException("No se encontró el llamado con ID: $llamadoId");
        }
        
        // Aplicar la sanción
        $llamado->setSancion($sancion);
        $this->llamadoMapper->update($llamado);
        
        return $llamado;
    }

    public function eliminarLlamado(int $llamadoId): bool
    {
        // Verificar que el llamado existe
        $llamado = $this->llamadoMapper->findById($llamadoId);
        if ($llamado === null) {
            throw new \InvalidArgumentException("No se encontró el llamado con ID: $llamadoId");
        }
        
        return $this->llamadoMapper->delete($llamadoId);
    }

    public function obtenerEstadisticas(
        ?int $estudianteId = null,
        ?DateTime $fechaInicio = null,
        ?DateTime $fechaFin = null
    ): array {
        return $this->llamadoMapper->getEstadisticas($estudianteId, $fechaInicio, $fechaFin);
    }

    public function obtenerHistorialDisciplinario(int $estudianteId): array
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($estudianteId);
        }
        
        // Obtener todos los llamados del estudiante
        $llamados = $this->obtenerPorEstudiante($estudianteId);
        
        // Calcular estadísticas
        $totalLlamados = count($llamados);
        $llamadosGraves = count(array_filter($llamados, fn($l) => $l->esGrave()));
        $conSancion = count(array_filter($llamados, fn($l) => !empty($l->getSancion())));
        
        // Agrupar por motivo
        $porMotivo = [];
        foreach ($llamados as $llamado) {
            $motivo = $llamado->getMotivo();
            if (!isset($porMotivo[$motivo])) {
                $porMotivo[$motivo] = 0;
            }
            $porMotivo[$motivo]++;
        }
        
        // Calcular tendencia (últimos 3 meses vs anteriores)
        $fechaActual = new DateTime();
        $fecha3Meses = new DateTime('-3 months');
        
        $llamadosRecientes = array_filter($llamados, function($l) use ($fecha3Meses) {
            return $l->getFecha() >= $fecha3Meses;
        });
        
        $llamadosAnteriores = array_filter($llamados, function($l) use ($fecha3Meses) {
            return $l->getFecha() < $fecha3Meses;
        });
        
        $tendencia = count($llamadosRecientes) > count($llamadosAnteriores) ? 'creciente' : 
                    (count($llamadosRecientes) < count($llamadosAnteriores) ? 'decreciente' : 'estable');
        
        return [
            'estudiante' => $estudiante,
            'total_llamados' => $totalLlamados,
            'llamados_graves' => $llamadosGraves,
            'con_sancion' => $conSancion,
            'por_motivo' => $porMotivo,
            'tendencia' => $tendencia,
            'llamados_recientes' => count($llamadosRecientes),
            'llamados_anteriores' => count($llamadosAnteriores),
            'ultimo_llamado' => !empty($llamados) ? $llamados[0]->getFecha() : null
        ];
    }

    public function tieneLlamadosRecientes(int $estudianteId, int $dias = 30): bool
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($estudianteId);
        }
        
        return $this->llamadoMapper->tieneLlamadosRecientes($estudianteId, $dias);
    }

    public function esEstudianteProblematico(int $estudianteId): bool
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($estudianteId);
        }
        
        // Un estudiante es problemático si tiene más de 3 llamados en los últimos 6 meses
        $fechaInicio = new DateTime('-6 months');
        $fechaFin = new DateTime();
        
        $estadisticas = $this->obtenerEstadisticas($estudianteId, $fechaInicio, $fechaFin);
        
        return $estadisticas['total_llamados'] > 3;
    }

    public function obtenerEstudiantesProblematicos(): array
    {
        $estudiantes = $this->estudianteMapper->findActive();
        $problematicos = [];
        
        foreach ($estudiantes as $estudiante) {
            if ($this->esEstudianteProblematico($estudiante->getId())) {
                $llamados = $this->obtenerPorEstudiante($estudiante->getId());
                $problematicos[] = [
                    'estudiante' => $estudiante,
                    'total_llamados' => count($llamados),
                    'llamados_graves' => count(array_filter($llamados, fn($l) => $l->esGrave())),
                    'ultimo_llamado' => !empty($llamados) ? $llamados[0]->getFecha() : null
                ];
            }
        }
        
        // Ordenar por total de llamados (descendente)
        usort($problematicos, fn($a, $b) => $b['total_llamados'] <=> $a['total_llamados']);
        
        return $problematicos;
    }

    public function obtenerResumenMensual(int $mes, int $anio): array
    {
        $fechaInicio = new DateTime("$anio-$mes-01");
        $fechaFin = new DateTime("$anio-$mes-" . $fechaInicio->format('t'));
        
        $llamados = $this->obtenerPorPeriodo($fechaInicio, $fechaFin);
        $estadisticas = $this->obtenerEstadisticas(null, $fechaInicio, $fechaFin);
        
        // Agrupar por día
        $porDia = [];
        foreach ($llamados as $llamado) {
            $dia = $llamado->getFecha()->format('d');
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = 0;
            }
            $porDia[$dia]++;
        }
        
        return [
            'mes' => $mes,
            'anio' => $anio,
            'total_llamados' => $estadisticas['total_llamados'],
            'llamados_graves' => $estadisticas['graves'],
            'con_sancion' => $estadisticas['con_sancion'],
            'por_dia' => $porDia,
            'promedio_diario' => count($llamados) / $fechaInicio->format('t')
        ];
    }

    /**
     * Método adicional para obtener llamados por tipo de motivo
     */
    public function obtenerLlamadosPorMotivo(string $motivo): array
    {
        $llamados = $this->llamadoMapper->findAll();
        
        return array_filter($llamados, function($llamado) use ($motivo) {
            return stripos($llamado->getMotivo(), $motivo) !== false;
        });
    }

    /**
     * Método adicional para validar si se puede registrar un llamado
     */
    public function puedeRegistrarLlamado(int $estudianteId): bool
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($estudianteId);
        if ($estudiante === null) {
            return false;
        }
        
        // Verificar que el estudiante esté activo
        if (!$estudiante->esActivo()) {
            return false;
        }
        
        return true;
    }
}
