<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioProfesores;
use SistemaAdmin\Models\Profesor;
use DateTime;

/**
 * Controller para manejar las peticiones HTTP relacionadas con profesores
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para profesores.
 */
class ProfesorController
{
    private ServicioProfesores $servicioProfesores;

    public function __construct(ServicioProfesores $servicioProfesores)
    {
        $this->servicioProfesores = $servicioProfesores;
    }

    /**
     * Maneja la petición GET para listar profesores
     */
    public function listar(array $filtros = []): array
    {
        try {
            $profesores = $this->servicioProfesores->obtenerTodos();
            
            // Aplicar filtros si se proporcionan
            if (!empty($filtros['especialidad'])) {
                $profesores = array_filter($profesores, function($profesor) use ($filtros) {
                    return stripos($profesor->getEspecialidad() ?? '', $filtros['especialidad']) !== false;
                });
            }
            
            if (!empty($filtros['search'])) {
                $search = strtolower($filtros['search']);
                $profesores = array_filter($profesores, function($profesor) use ($search) {
                    return stripos($profesor->getApellido(), $search) !== false ||
                           stripos($profesor->getNombre(), $search) !== false ||
                           stripos($profesor->getDni(), $search) !== false;
                });
            }
            
            $profesoresArray = array_map(function($profesor) {
                return [
                    'id' => $profesor->getId(),
                    'dni' => $profesor->getDni(),
                    'apellido' => $profesor->getApellido(),
                    'nombre' => $profesor->getNombre(),
                    'nombre_completo' => $profesor->getNombreCompleto(),
                    'fecha_nacimiento' => $profesor->getFechaNacimiento()?->format('Y-m-d'),
                    'edad' => $profesor->getEdad(),
                    'especialidad' => $profesor->getEspecialidad(),
                    'titulo' => $profesor->getTitulo(),
                    'telefono_fijo' => $profesor->getTelefonoFijo(),
                    'telefono_celular' => $profesor->getTelefonoCelular(),
                    'email' => $profesor->getEmail(),
                    'domicilio' => $profesor->getDomicilio(),
                    'fecha_ingreso' => $profesor->getFechaIngreso()?->format('Y-m-d'),
                    'activo' => $profesor->esActivo(),
                    'tiene_cursos' => $this->servicioProfesores->tieneCursosAsignados($profesor->getId())
                ];
            }, $profesores);
            
            return [
                'success' => true,
                'data' => array_values($profesoresArray),
                'total' => count($profesoresArray)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener un profesor por ID
     */
    public function obtener(int $profesorId): array
    {
        try {
            $profesor = $this->servicioProfesores->obtenerPorId($profesorId);
            
            return [
                'success' => true,
                'data' => [
                    'id' => $profesor->getId(),
                    'dni' => $profesor->getDni(),
                    'apellido' => $profesor->getApellido(),
                    'nombre' => $profesor->getNombre(),
                    'nombre_completo' => $profesor->getNombreCompleto(),
                    'fecha_nacimiento' => $profesor->getFechaNacimiento()?->format('Y-m-d'),
                    'edad' => $profesor->getEdad(),
                    'especialidad' => $profesor->getEspecialidad(),
                    'titulo' => $profesor->getTitulo(),
                    'telefono_fijo' => $profesor->getTelefonoFijo(),
                    'telefono_celular' => $profesor->getTelefonoCelular(),
                    'email' => $profesor->getEmail(),
                    'domicilio' => $profesor->getDomicilio(),
                    'fecha_ingreso' => $profesor->getFechaIngreso()?->format('Y-m-d'),
                    'activo' => $profesor->esActivo(),
                    'cursos_asignados' => $this->servicioProfesores->obtenerCursosAsignados($profesorId),
                    'materias_asignadas' => $this->servicioProfesores->obtenerMateriasAsignadas($profesorId)
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
     * Maneja la petición POST para crear un nuevo profesor
     */
    public function crear(array $datos): array
    {
        try {
            // Validar datos requeridos
            $errores = $this->validarDatosProfesor($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Crear el objeto Profesor
            $profesor = new Profesor(
                $datos['dni'],
                $datos['apellido'],
                $datos['nombre'],
                !empty($datos['fecha_nacimiento']) ? new DateTime($datos['fecha_nacimiento']) : null,
                $datos['domicilio'] ?? null,
                $datos['telefono_fijo'] ?? null,
                $datos['telefono_celular'] ?? null,
                $datos['email'] ?? null,
                $datos['titulo'] ?? null,
                $datos['especialidad'] ?? null,
                !empty($datos['fecha_ingreso']) ? new DateTime($datos['fecha_ingreso']) : null
            );
            
            // Guardar el profesor
            $profesorGuardado = $this->servicioProfesores->crear($profesor);
            
            return [
                'success' => true,
                'data' => [
                    'id' => $profesorGuardado->getId(),
                    'nombre_completo' => $profesorGuardado->getNombreCompleto(),
                    'dni' => $profesorGuardado->getDni()
                ],
                'message' => 'Profesor creado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición PUT para actualizar un profesor
     */
    public function actualizar(int $profesorId, array $datos): array
    {
        try {
            // Validar datos requeridos
            $errores = $this->validarDatosProfesor($datos, $profesorId);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Obtener el profesor existente
            $profesor = $this->servicioProfesores->obtenerPorId($profesorId);
            
            // Actualizar los datos
            $profesor->setDni($datos['dni']);
            $profesor->setApellido($datos['apellido']);
            $profesor->setNombre($datos['nombre']);
            $profesor->setFechaNacimiento(!empty($datos['fecha_nacimiento']) ? new DateTime($datos['fecha_nacimiento']) : null);
            $profesor->setDomicilio($datos['domicilio'] ?? null);
            $profesor->setTelefonoFijo($datos['telefono_fijo'] ?? null);
            $profesor->setTelefonoCelular($datos['telefono_celular'] ?? null);
            $profesor->setEmail($datos['email'] ?? null);
            $profesor->setTitulo($datos['titulo'] ?? null);
            $profesor->setEspecialidad($datos['especialidad'] ?? null);
            $profesor->setFechaIngreso(!empty($datos['fecha_ingreso']) ? new DateTime($datos['fecha_ingreso']) : null);
            
            // Guardar los cambios
            $this->servicioProfesores->actualizar($profesor);
            
            return [
                'success' => true,
                'data' => [
                    'id' => $profesor->getId(),
                    'nombre_completo' => $profesor->getNombreCompleto()
                ],
                'message' => 'Profesor actualizado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición DELETE para eliminar un profesor
     */
    public function eliminar(int $profesorId): array
    {
        try {
            $this->servicioProfesores->eliminar($profesorId);
            
            return [
                'success' => true,
                'message' => 'Profesor eliminado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para buscar profesores
     */
    public function buscar(string $termino): array
    {
        try {
            $profesores = $this->servicioProfesores->buscarPorNombre($termino);
            
            $profesoresArray = array_map(function($profesor) {
                return [
                    'id' => $profesor->getId(),
                    'dni' => $profesor->getDni(),
                    'apellido' => $profesor->getApellido(),
                    'nombre' => $profesor->getNombre(),
                    'nombre_completo' => $profesor->getNombreCompleto(),
                    'fecha_nacimiento' => $profesor->getFechaNacimiento()?->format('Y-m-d'),
                    'edad' => $profesor->getEdad(),
                    'especialidad' => $profesor->getEspecialidad(),
                    'titulo' => $profesor->getTitulo(),
                    'telefono_fijo' => $profesor->getTelefonoFijo(),
                    'telefono_celular' => $profesor->getTelefonoCelular(),
                    'email' => $profesor->getEmail(),
                    'domicilio' => $profesor->getDomicilio(),
                    'fecha_ingreso' => $profesor->getFechaIngreso()?->format('Y-m-d'),
                    'activo' => $profesor->esActivo(),
                    'tiene_cursos' => $this->servicioProfesores->tieneCursosAsignados($profesor->getId())
                ];
            }, $profesores);
            
            return [
                'success' => true,
                'data' => $profesoresArray,
                'total' => count($profesoresArray)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener estadísticas
     */
    public function estadisticas(): array
    {
        try {
            $estadisticas = $this->servicioProfesores->obtenerEstadisticas();
            
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
     * Maneja la petición GET para obtener profesores por especialidad
     */
    public function porEspecialidad(string $especialidad): array
    {
        try {
            $profesores = $this->servicioProfesores->buscarPorEspecialidad($especialidad);
            
            $profesoresArray = array_map(function($profesor) {
                return [
                    'id' => $profesor->getId(),
                    'nombre_completo' => $profesor->getNombreCompleto(),
                    'dni' => $profesor->getDni(),
                    'titulo' => $profesor->getTitulo()
                ];
            }, $profesores);
            
            return [
                'success' => true,
                'data' => $profesoresArray,
                'total' => count($profesoresArray)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener profesores sin cursos
     */
    public function sinCursos(): array
    {
        try {
            $profesores = $this->servicioProfesores->obtenerProfesoresSinCursos();
            
            $profesoresArray = array_map(function($profesor) {
                return [
                    'id' => $profesor->getId(),
                    'nombre_completo' => $profesor->getNombreCompleto(),
                    'dni' => $profesor->getDni(),
                    'especialidad' => $profesor->getEspecialidad(),
                    'fecha_ingreso' => $profesor->getFechaIngreso()?->format('Y-m-d')
                ];
            }, $profesores);
            
            return [
                'success' => true,
                'data' => $profesoresArray,
                'total' => count($profesoresArray)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida los datos de un profesor
     */
    private function validarDatosProfesor(array $datos, ?int $profesorId = null): array
    {
        $errores = [];
        
        if (empty($datos['dni'])) {
            $errores[] = 'El DNI es requerido';
        }
        
        if (empty($datos['apellido'])) {
            $errores[] = 'El apellido es requerido';
        }
        
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        }
        
        // Validar formato de DNI
        if (!empty($datos['dni']) && !preg_match('/^\d{7,8}$/', $datos['dni'])) {
            $errores[] = 'El DNI debe tener entre 7 y 8 dígitos';
        }
        
        // Validar email si se proporciona
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El formato del email es inválido';
        }
        
        return $errores;
    }
}
