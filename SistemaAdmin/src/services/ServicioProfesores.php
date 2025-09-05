<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Interfaces\IServicioProfesores;
use SistemaAdmin\Models\Profesor;
use SistemaAdmin\Mappers\ProfesorMapper;
use SistemaAdmin\Exceptions\ProfesorNoEncontradoException;
use DateTime;

/**
 * Implementación concreta del ServicioProfesores
 * 
 * Contiene la lógica de negocio para la gestión de profesores.
 * Implementa la interfaz IServicioProfesores.
 */
class ServicioProfesores implements IServicioProfesores
{
    private ProfesorMapper $profesorMapper;

    public function __construct(ProfesorMapper $profesorMapper)
    {
        $this->profesorMapper = $profesorMapper;
    }

    public function crear(Profesor $profesor): Profesor
    {
        // Validar que el DNI no esté duplicado
        $profesorExistente = $this->profesorMapper->findByDni($profesor->getDni());
        if ($profesorExistente !== null) {
            throw new \InvalidArgumentException("Ya existe un profesor con el DNI: " . $profesor->getDni());
        }
        
        // Validar datos del profesor
        $this->validarProfesor($profesor);
        
        // Guardar en la base de datos
        return $this->profesorMapper->save($profesor);
    }

    public function actualizar(Profesor $profesor): Profesor
    {
        // Verificar que el profesor existe
        $profesorExistente = $this->profesorMapper->findById($profesor->getId());
        if ($profesorExistente === null) {
            throw new ProfesorNoEncontradoException($profesor->getId());
        }
        
        // Validar datos del profesor
        $this->validarProfesor($profesor);
        
        // Actualizar en la base de datos
        $this->profesorMapper->update($profesor);
        
        return $profesor;
    }

    public function eliminar(int $profesorId): bool
    {
        // Verificar que el profesor existe
        $profesor = $this->profesorMapper->findById($profesorId);
        if ($profesor === null) {
            throw new ProfesorNoEncontradoException($profesorId);
        }
        
        // Verificar si tiene cursos asignados
        if ($this->tieneCursosAsignados($profesorId)) {
            throw new \InvalidArgumentException("No se puede eliminar el profesor porque tiene cursos asignados. Primero debe desasignar los cursos.");
        }
        
        // Marcar como inactivo en lugar de eliminar
        return $this->profesorMapper->delete($profesorId);
    }

    public function buscarPorId(int $id): ?Profesor
    {
        return $this->profesorMapper->findById($id);
    }

    public function buscarPorDni(string $dni): ?Profesor
    {
        return $this->profesorMapper->findByDni($dni);
    }

    public function obtenerPorId(int $profesorId): Profesor
    {
        $profesor = $this->profesorMapper->findById($profesorId);
        if ($profesor === null) {
            throw new ProfesorNoEncontradoException($profesorId);
        }
        
        return $profesor;
    }

    public function obtenerTodos(): array
    {
        return $this->profesorMapper->findActive();
    }

    public function buscarPorNombre(string $nombre): array
    {
        return $this->profesorMapper->findByNombre($nombre);
    }

    public function obtenerPorEspecialidad(string $especialidad): array
    {
        return $this->profesorMapper->findByEspecialidad($especialidad);
    }

    public function buscarPorEspecialidad(string $especialidad): array
    {
        return $this->profesorMapper->findByEspecialidad($especialidad);
    }

    public function obtenerPorMateriaYCurso(int $materiaId, int $cursoId): array
    {
        return $this->profesorMapper->findByMateriaYCurso($materiaId, $cursoId);
    }

    public function obtenerEstadisticas(): array
    {
        $profesores = $this->obtenerTodos();
        $totalProfesores = count($profesores);
        
        // Contar profesores con y sin cursos asignados
        $conCursos = 0;
        $sinCursos = 0;
        $especialidades = [];
        
        foreach ($profesores as $profesor) {
            if ($this->tieneCursosAsignados($profesor->getId())) {
                $conCursos++;
            } else {
                $sinCursos++;
            }
            
            if ($profesor->getEspecialidad()) {
                $especialidad = $profesor->getEspecialidad();
                if (!isset($especialidades[$especialidad])) {
                    $especialidades[$especialidad] = 0;
                }
                $especialidades[$especialidad]++;
            }
        }
        
        return [
            'total_profesores' => $totalProfesores,
            'con_cursos' => $conCursos,
            'sin_cursos' => $sinCursos,
            'especialidades' => $especialidades,
            'promedio_edad' => $this->calcularPromedioEdad($profesores)
        ];
    }

    public function tieneCursosAsignados(int $profesorId): bool
    {
        return $this->profesorMapper->tieneCursosAsignados($profesorId);
    }

    public function obtenerCursosAsignados(int $profesorId): array
    {
        return $this->profesorMapper->getCursosAsignados($profesorId);
    }

    public function obtenerMateriasAsignadas(int $profesorId): array
    {
        return $this->profesorMapper->getMateriasAsignadas($profesorId);
    }

    public function esProfesorActivo(int $profesorId): bool
    {
        $profesor = $this->profesorMapper->findById($profesorId);
        return $profesor !== null && $profesor->esActivo();
    }

    public function obtenerProfesoresSinCursos(): array
    {
        $profesores = $this->obtenerTodos();
        
        return array_filter($profesores, function($profesor) {
            return !$this->tieneCursosAsignados($profesor->getId());
        });
    }

    public function obtenerProfesoresPorEspecialidad(): array
    {
        $profesores = $this->obtenerTodos();
        $porEspecialidad = [];
        
        foreach ($profesores as $profesor) {
            $especialidad = $profesor->getEspecialidad() ?: 'Sin especialidad';
            if (!isset($porEspecialidad[$especialidad])) {
                $porEspecialidad[$especialidad] = [];
            }
            $porEspecialidad[$especialidad][] = $profesor;
        }
        
        return $porEspecialidad;
    }

    public function obtenerResumenMensual(int $mes, int $anio): array
    {
        $profesores = $this->obtenerTodos();
        $fechaInicio = new DateTime("$anio-$mes-01");
        $fechaFin = new DateTime("$anio-$mes-" . $fechaInicio->format('t'));
        
        $nuevosProfesores = 0;
        $profesoresActivos = 0;
        
        foreach ($profesores as $profesor) {
            if ($profesor->getFechaIngreso() && 
                $profesor->getFechaIngreso() >= $fechaInicio && 
                $profesor->getFechaIngreso() <= $fechaFin) {
                $nuevosProfesores++;
            }
            
            if ($profesor->esActivo()) {
                $profesoresActivos++;
            }
        }
        
        return [
            'mes' => $mes,
            'anio' => $anio,
            'nuevos_profesores' => $nuevosProfesores,
            'profesores_activos' => $profesoresActivos,
            'total_profesores' => count($profesores)
        ];
    }

    /**
     * Valida los datos de un profesor
     */
    private function validarProfesor(Profesor $profesor): void
    {
        if (empty(trim($profesor->getDni()))) {
            throw new \InvalidArgumentException("El DNI es requerido");
        }
        
        if (empty(trim($profesor->getApellido()))) {
            throw new \InvalidArgumentException("El apellido es requerido");
        }
        
        if (empty(trim($profesor->getNombre()))) {
            throw new \InvalidArgumentException("El nombre es requerido");
        }
        
        // Validar formato de DNI
        if (!preg_match('/^\d{7,8}$/', $profesor->getDni())) {
            throw new \InvalidArgumentException("El DNI debe tener entre 7 y 8 dígitos");
        }
        
        // Validar email si se proporciona
        if ($profesor->getEmail() && !filter_var($profesor->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("El formato del email es inválido");
        }
    }

    public function dniExiste(string $dni, ?int $excluirId = null): bool
    {
        $profesor = $this->profesorMapper->findByDni($dni);
        
        if ($profesor === null) {
            return false;
        }
        
        // Si se proporciona un ID a excluir, verificar que no sea el mismo profesor
        if ($excluirId !== null && $profesor->getId() === $excluirId) {
            return false;
        }
        
        return true;
    }

    public function obtenerPorMateria(int $materiaId): array
    {
        return $this->profesorMapper->findByMateria($materiaId);
    }

    /**
     * Calcula el promedio de edad de los profesores
     */
    private function calcularPromedioEdad(array $profesores): float
    {
        $edades = [];
        
        foreach ($profesores as $profesor) {
            if ($profesor->getFechaNacimiento()) {
                $edades[] = $profesor->getEdad();
            }
        }
        
        return !empty($edades) ? array_sum($edades) / count($edades) : 0.0;
    }
}
