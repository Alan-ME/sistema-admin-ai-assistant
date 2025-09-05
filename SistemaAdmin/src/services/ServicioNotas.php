<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Interfaces\IServicioNotas;
use SistemaAdmin\Models\Nota;
use SistemaAdmin\Mappers\NotaMapper;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Exceptions\EstudianteNoEncontradoException;
use SistemaAdmin\Exceptions\CalificacionInvalidaException;

/**
 * Implementación concreta del ServicioNotas
 * 
 * Contiene la lógica de negocio para la gestión de calificaciones.
 * Implementa la interfaz IServicioNotas siguiendo el ejemplo del documento.
 */
class ServicioNotas implements IServicioNotas
{
    private NotaMapper $notaMapper;
    private EstudianteMapper $estudianteMapper;

    public function __construct(NotaMapper $notaMapper, EstudianteMapper $estudianteMapper)
    {
        $this->notaMapper = $notaMapper;
        $this->estudianteMapper = $estudianteMapper;
    }

    public function cargarNota(
        int $idEstudiante, 
        int $idMateria, 
        float $calificacion, 
        string $bimestre,
        ?string $observaciones = null
    ): Nota {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($idEstudiante);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($idEstudiante);
        }
        
        // Validar la calificación
        if ($calificacion < Nota::VALOR_MINIMO || $calificacion > Nota::VALOR_MAXIMO) {
            throw new CalificacionInvalidaException($calificacion);
        }
        
        // Validar el bimestre
        if (!in_array($bimestre, Nota::BIMESTRES_VALIDOS)) {
            throw new \InvalidArgumentException(
                "Bimestre inválido: $bimestre. Debe ser uno de: " . 
                implode(', ', Nota::BIMESTRES_VALIDOS)
            );
        }
        
        // Crear la nota
        $nota = new Nota($idEstudiante, $idMateria, $calificacion, $bimestre, $observaciones);
        
        // Guardar en la base de datos
        return $this->notaMapper->save($nota);
    }

    public function obtenerPromedioMateria(int $idEstudiante, int $idMateria): float
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($idEstudiante);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($idEstudiante);
        }
        
        return $this->notaMapper->getPromedioMateria($idEstudiante, $idMateria);
    }

    public function obtenerPromedioEstudiante(int $idEstudiante): float
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($idEstudiante);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($idEstudiante);
        }
        
        return $this->notaMapper->getPromedioGeneral($idEstudiante);
    }

    public function obtenerNotasEstudiante(int $idEstudiante): array
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($idEstudiante);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($idEstudiante);
        }
        
        return $this->notaMapper->findByEstudiante($idEstudiante);
    }

    public function obtenerNotasMateria(int $idEstudiante, int $idMateria): array
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($idEstudiante);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($idEstudiante);
        }
        
        return $this->notaMapper->findByMateria($idEstudiante, $idMateria);
    }

    public function obtenerNotasBimestre(int $idEstudiante, string $bimestre): array
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($idEstudiante);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($idEstudiante);
        }
        
        return $this->notaMapper->findByBimestre($idEstudiante, $bimestre);
    }

    public function actualizarNota(int $idNota, float $nuevoValor, ?string $observaciones = null): Nota
    {
        // Buscar la nota
        $nota = $this->notaMapper->findById($idNota);
        if ($nota === null) {
            throw new \InvalidArgumentException("No se encontró la nota con ID: $idNota");
        }
        
        // Validar el nuevo valor
        if ($nuevoValor < Nota::VALOR_MINIMO || $nuevoValor > Nota::VALOR_MAXIMO) {
            throw new CalificacionInvalidaException($nuevoValor);
        }
        
        // Actualizar la nota
        $nota->setValor($nuevoValor);
        if ($observaciones !== null) {
            $nota->setObservaciones($observaciones);
        }
        
        $this->notaMapper->update($nota);
        return $nota;
    }

    public function eliminarNota(int $idNota): bool
    {
        // Verificar que la nota existe
        $nota = $this->notaMapper->findById($idNota);
        if ($nota === null) {
            throw new \InvalidArgumentException("No se encontró la nota con ID: $idNota");
        }
        
        return $this->notaMapper->delete($idNota);
    }

    public function obtenerEstadisticas(?int $idMateria = null, ?string $bimestre = null): array
    {
        return $this->notaMapper->getEstadisticas($idMateria, $bimestre);
    }

    public function estaAprobado(int $idEstudiante, int $idMateria): bool
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($idEstudiante);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($idEstudiante);
        }
        
        $promedio = $this->obtenerPromedioMateria($idEstudiante, $idMateria);
        return $promedio >= Nota::VALOR_APROBACION;
    }

    public function obtenerBoletin(int $idEstudiante): array
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($idEstudiante);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($idEstudiante);
        }
        
        $notas = $this->obtenerNotasEstudiante($idEstudiante);
        $promedioGeneral = $this->obtenerPromedioEstudiante($idEstudiante);
        
        // Agrupar notas por materia
        $notasPorMateria = [];
        foreach ($notas as $nota) {
            $materiaId = $nota->getMateriaId();
            if (!isset($notasPorMateria[$materiaId])) {
                $notasPorMateria[$materiaId] = [];
            }
            $notasPorMateria[$materiaId][] = $nota;
        }
        
        // Calcular promedios por materia
        $promediosPorMateria = [];
        foreach ($notasPorMateria as $materiaId => $notasMateria) {
            $promedioMateria = $this->obtenerPromedioMateria($idEstudiante, $materiaId);
            $promediosPorMateria[$materiaId] = [
                'promedio' => $promedioMateria,
                'aprobado' => $promedioMateria >= Nota::VALOR_APROBACION,
                'notas' => array_map(fn($nota) => $nota->toArray(), $notasMateria)
            ];
        }
        
        return [
            'estudiante' => $estudiante->toArray(),
            'promedio_general' => $promedioGeneral,
            'promedios_por_materia' => $promediosPorMateria,
            'total_materias' => count($promediosPorMateria),
            'materias_aprobadas' => count(array_filter($promediosPorMateria, fn($m) => $m['aprobado'])),
            'materias_desaprobadas' => count(array_filter($promediosPorMateria, fn($m) => !$m['aprobado']))
        ];
    }

    /**
     * Método adicional para obtener notas recientes
     */
    public function obtenerNotasRecientes(int $dias = 30): array
    {
        $notas = $this->notaMapper->findAll();
        $fechaLimite = new \DateTime("-{$dias} days");
        
        return array_filter($notas, function($nota) use ($fechaLimite) {
            return $nota->getFecha() >= $fechaLimite;
        });
    }

    /**
     * Método adicional para validar si se puede cargar una nota
     */
    public function puedeCargarNota(int $idEstudiante, int $idMateria, string $bimestre): bool
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($idEstudiante);
        if ($estudiante === null) {
            return false;
        }
        
        // Verificar que no se haya cargado ya una nota para este bimestre
        $notasBimestre = $this->obtenerNotasBimestre($idEstudiante, $bimestre);
        foreach ($notasBimestre as $nota) {
            if ($nota->getMateriaId() === $idMateria) {
                return false; // Ya existe una nota para esta materia en este bimestre
            }
        }
        
        return true;
    }
}
