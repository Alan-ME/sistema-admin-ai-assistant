<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Interfaces\IServicioEstudiantes;
use SistemaAdmin\Models\Estudiante;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Exceptions\EstudianteNoEncontradoException;
use DateTime;

/**
 * Implementación concreta del ServicioEstudiantes
 * 
 * Contiene la lógica de negocio para la gestión de estudiantes.
 * Implementa la interfaz IServicioEstudiantes.
 */
class ServicioEstudiantes implements IServicioEstudiantes
{
    private EstudianteMapper $estudianteMapper;
    private CacheService $cacheService;
    private PaginationService $paginationService;

    public function __construct(EstudianteMapper $estudianteMapper, CacheService $cacheService = null, PaginationService $paginationService = null)
    {
        $this->estudianteMapper = $estudianteMapper;
        $this->cacheService = $cacheService ?? new CacheService($estudianteMapper->getDatabase());
        $this->paginationService = $paginationService ?? new PaginationService();
    }

    public function buscarPorId(int $id): ?Estudiante
    {
        $estudiante = $this->estudianteMapper->findById($id);
        
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($id);
        }
        
        return $estudiante;
    }

    public function buscarPorDni(string $dni): ?Estudiante
    {
        return $this->estudianteMapper->findByDni($dni);
    }

    public function obtenerTodos(): array
    {
        return $this->estudianteMapper->findActive();
    }

    public function obtenerPorCurso(int $cursoId): array
    {
        return $this->estudianteMapper->findBy(['curso_id' => $cursoId, 'activo' => 1]);
    }

    public function buscarPorNombre(string $termino): array
    {
        // Búsqueda más sofisticada que incluye nombre y apellido
        $estudiantes = $this->estudianteMapper->findActive();
        
        $termino = strtolower(trim($termino));
        $resultados = [];
        
        foreach ($estudiantes as $estudiante) {
            $nombreCompleto = strtolower($estudiante->getNombreCompleto());
            $nombre = strtolower($estudiante->getNombre());
            $apellido = strtolower($estudiante->getApellido());
            
            if (strpos($nombreCompleto, $termino) !== false || 
                strpos($nombre, $termino) !== false || 
                strpos($apellido, $termino) !== false) {
                $resultados[] = $estudiante;
            }
        }
        
        return $resultados;
    }

    public function crear(Estudiante $estudiante): Estudiante
    {
        // Validar que el DNI no exista
        if ($this->dniExiste($estudiante->getDni())) {
            throw new \InvalidArgumentException("Ya existe un estudiante con el DNI: " . $estudiante->getDni());
        }
        
        // Validar que el estudiante sea válido (las validaciones están en el modelo)
        return $this->estudianteMapper->save($estudiante);
    }

    public function actualizar(Estudiante $estudiante): Estudiante
    {
        // Verificar que el estudiante existe
        $estudianteExistente = $this->estudianteMapper->findById($estudiante->getId());
        if ($estudianteExistente === null) {
            throw new EstudianteNoEncontradoException($estudiante->getId());
        }
        
        // Validar que el DNI no esté en uso por otro estudiante
        if ($this->dniExiste($estudiante->getDni(), $estudiante->getId())) {
            throw new \InvalidArgumentException("Ya existe otro estudiante con el DNI: " . $estudiante->getDni());
        }
        
        // Actualizar
        $this->estudianteMapper->update($estudiante);
        return $estudiante;
    }

    public function eliminar(int $id): bool
    {
        // Verificar que el estudiante existe
        $estudiante = $this->estudianteMapper->findById($id);
        if ($estudiante === null) {
            throw new EstudianteNoEncontradoException($id);
        }
        
        return $this->estudianteMapper->delete($id);
    }

    public function obtenerEstadisticas(): array
    {
        $totalEstudiantes = $this->estudianteMapper->countBy(['activo' => 1]);
        $estudiantes = $this->estudianteMapper->findActive();
        
        // Estadísticas por curso
        $porCurso = [];
        $mayoresDeEdad = 0;
        $conContacto = 0;
        
        foreach ($estudiantes as $estudiante) {
            $cursoId = $estudiante->getCursoId();
            if ($cursoId) {
                if (!isset($porCurso[$cursoId])) {
                    $porCurso[$cursoId] = 0;
                }
                $porCurso[$cursoId]++;
            }
            
            if ($estudiante->esMayorDeEdad()) {
                $mayoresDeEdad++;
            }
            
            if ($estudiante->tieneContacto()) {
                $conContacto++;
            }
        }
        
        return [
            'total_estudiantes' => $totalEstudiantes,
            'mayores_de_edad' => $mayoresDeEdad,
            'menores_de_edad' => $totalEstudiantes - $mayoresDeEdad,
            'con_contacto' => $conContacto,
            'sin_contacto' => $totalEstudiantes - $conContacto,
            'por_curso' => $porCurso
        ];
    }

    public function obtenerCumpleaneros(DateTime $fecha): array
    {
        $estudiantes = $this->estudianteMapper->findActive();
        $cumpleaneros = [];
        
        $mes = $fecha->format('m');
        $dia = $fecha->format('d');
        
        foreach ($estudiantes as $estudiante) {
            $fechaNacimiento = $estudiante->getFechaNacimiento();
            if ($fechaNacimiento && 
                $fechaNacimiento->format('m') === $mes && 
                $fechaNacimiento->format('d') === $dia) {
                $cumpleaneros[] = $estudiante;
            }
        }
        
        return $cumpleaneros;
    }

    public function dniExiste(string $dni, ?int $excluirId = null): bool
    {
        return $this->estudianteMapper->existsByDni($dni, $excluirId);
    }

    /**
     * Método adicional para obtener estudiantes con información de curso
     */
    public function obtenerConInformacionCurso(): array
    {
        // Este método podría expandirse para incluir información del curso
        // usando joins o consultas adicionales
        return $this->estudianteMapper->findActive();
    }

    /**
     * Método adicional para validar datos antes de guardar
     */
    public function validarDatosEstudiante(Estudiante $estudiante): array
    {
        $errores = [];
        
        // Validar DNI
        if ($this->dniExiste($estudiante->getDni(), $estudiante->getId())) {
            $errores[] = "El DNI ya está en uso";
        }
        
        // Validar email si existe
        if ($estudiante->getEmail() && !filter_var($estudiante->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El email no es válido";
        }
        
        // Validar fecha de nacimiento
        if ($estudiante->getFechaNacimiento()) {
            $edad = $estudiante->getEdad();
            if ($edad < 0 || $edad > 100) {
                $errores[] = "La edad debe estar entre 0 y 100 años";
            }
        }
        
        return $errores;
    }

    /**
     * Obtener estudiantes con paginación
     */
    public function obtenerConPaginacion(int $page = 1, int $pageSize = 20, array $filtros = []): array
    {
        $cacheKey = "estudiantes:page:{$page}:size:{$pageSize}:" . md5(serialize($filtros));
        
        return $this->cacheService->remember($cacheKey, function() use ($page, $pageSize, $filtros) {
            // Contar total de estudiantes
            $totalItems = $this->estudianteMapper->countBy(array_merge(['activo' => 1], $filtros));
            
            // Calcular paginación
            $pagination = $this->paginationService->calculatePagination($totalItems, $page, $pageSize);
            
            // Obtener estudiantes paginados
            $estudiantes = $this->estudianteMapper->findWithPagination($pagination['offset'], $pagination['page_size'], $filtros);
            
            return [
                'estudiantes' => $estudiantes,
                'pagination' => $pagination
            ];
        }, 300); // Cache por 5 minutos
    }

    /**
     * Buscar estudiantes con paginación
     */
    public function buscarConPaginacion(string $termino, int $page = 1, int $pageSize = 20): array
    {
        $cacheKey = "estudiantes:search:{$termino}:page:{$page}:size:{$pageSize}";
        
        return $this->cacheService->remember($cacheKey, function() use ($termino, $page, $pageSize) {
            // Obtener todos los estudiantes que coinciden
            $estudiantes = $this->buscarPorNombre($termino);
            $totalItems = count($estudiantes);
            
            // Calcular paginación
            $pagination = $this->paginationService->calculatePagination($totalItems, $page, $pageSize);
            
            // Aplicar paginación manualmente
            $estudiantesPaginados = array_slice($estudiantes, $pagination['offset'], $pagination['page_size']);
            
            return [
                'estudiantes' => $estudiantesPaginados,
                'pagination' => $pagination
            ];
        }, 180); // Cache por 3 minutos
    }

    /**
     * Obtener estudiantes por curso con paginación
     */
    public function obtenerPorCursoConPaginacion(int $cursoId, int $page = 1, int $pageSize = 20): array
    {
        $cacheKey = "estudiantes:curso:{$cursoId}:page:{$page}:size:{$pageSize}";
        
        return $this->cacheService->remember($cacheKey, function() use ($cursoId, $page, $pageSize) {
            // Contar total de estudiantes en el curso
            $totalItems = $this->estudianteMapper->countBy(['curso_id' => $cursoId, 'activo' => 1]);
            
            // Calcular paginación
            $pagination = $this->paginationService->calculatePagination($totalItems, $page, $pageSize);
            
            // Obtener estudiantes paginados
            $estudiantes = $this->estudianteMapper->findWithPagination(
                $pagination['offset'], 
                $pagination['page_size'], 
                ['curso_id' => $cursoId, 'activo' => 1]
            );
            
            return [
                'estudiantes' => $estudiantes,
                'pagination' => $pagination
            ];
        }, 300); // Cache por 5 minutos
    }

    /**
     * Invalidar cache de estudiantes
     */
    public function invalidarCache(): void
    {
        $this->cacheService->invalidatePattern("estudiantes:*");
    }

    /**
     * Obtener estadísticas con cache
     */
    public function obtenerEstadisticasConCache(): array
    {
        $cacheKey = "estadisticas:estudiantes";
        
        return $this->cacheService->remember($cacheKey, function() {
            return $this->obtenerEstadisticas();
        }, 600); // Cache por 10 minutos
    }
}
