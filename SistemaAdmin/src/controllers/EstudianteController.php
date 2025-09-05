<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Models\Estudiante;
use DateTime;

/**
 * Controller para manejar las peticiones HTTP relacionadas con estudiantes
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio.
 */
class EstudianteController
{
    private ServicioEstudiantes $servicioEstudiantes;

    public function __construct(ServicioEstudiantes $servicioEstudiantes)
    {
        $this->servicioEstudiantes = $servicioEstudiantes;
    }

    /**
     * Maneja la petición GET para listar estudiantes
     */
    public function listar(): array
    {
        try {
            $estudiantes = $this->servicioEstudiantes->obtenerTodos();
            
            return [
                'success' => true,
                'data' => array_map(fn($estudiante) => $estudiante->toArray(), $estudiantes),
                'total' => count($estudiantes)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener un estudiante por ID
     */
    public function obtener(int $id): array
    {
        try {
            $estudiante = $this->servicioEstudiantes->buscarPorId($id);
            
            return [
                'success' => true,
                'data' => $estudiante->toArray()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición POST para crear un nuevo estudiante
     */
    public function crear(array $datos): array
    {
        try {
            // Validar datos requeridos
            $errores = $this->validarDatosCreacion($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Crear el estudiante
            $estudiante = new Estudiante(
                $datos['dni'],
                $datos['nombre'],
                $datos['apellido']
            );
            
            // Establecer datos opcionales
            if (isset($datos['fecha_nacimiento']) && !empty($datos['fecha_nacimiento'])) {
                $estudiante->setFechaNacimiento(new DateTime($datos['fecha_nacimiento']));
            }
            
            if (isset($datos['email'])) {
                $estudiante->setEmail($datos['email']);
            }
            
            if (isset($datos['telefono_celular'])) {
                $estudiante->setTelefonoCelular($datos['telefono_celular']);
            }
            
            if (isset($datos['domicilio'])) {
                $estudiante->setDomicilio($datos['domicilio']);
            }
            
            if (isset($datos['curso_id'])) {
                $estudiante->setCursoId($datos['curso_id']);
            }
            
            // Guardar
            $estudianteGuardado = $this->servicioEstudiantes->crear($estudiante);
            
            return [
                'success' => true,
                'data' => $estudianteGuardado->toArray(),
                'message' => 'Estudiante creado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición PUT para actualizar un estudiante
     */
    public function actualizar(int $id, array $datos): array
    {
        try {
            // Obtener el estudiante existente
            $estudiante = $this->servicioEstudiantes->buscarPorId($id);
            
            // Validar datos
            $errores = $this->validarDatosActualizacion($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Actualizar campos
            if (isset($datos['dni'])) {
                $estudiante->setDni($datos['dni']);
            }
            
            if (isset($datos['nombre'])) {
                $estudiante->setNombre($datos['nombre']);
            }
            
            if (isset($datos['apellido'])) {
                $estudiante->setApellido($datos['apellido']);
            }
            
            if (isset($datos['fecha_nacimiento'])) {
                $estudiante->setFechaNacimiento(
                    !empty($datos['fecha_nacimiento']) ? new DateTime($datos['fecha_nacimiento']) : null
                );
            }
            
            if (isset($datos['email'])) {
                $estudiante->setEmail($datos['email']);
            }
            
            if (isset($datos['telefono_celular'])) {
                $estudiante->setTelefonoCelular($datos['telefono_celular']);
            }
            
            if (isset($datos['domicilio'])) {
                $estudiante->setDomicilio($datos['domicilio']);
            }
            
            if (isset($datos['curso_id'])) {
                $estudiante->setCursoId($datos['curso_id']);
            }
            
            // Guardar cambios
            $estudianteActualizado = $this->servicioEstudiantes->actualizar($estudiante);
            
            return [
                'success' => true,
                'data' => $estudianteActualizado->toArray(),
                'message' => 'Estudiante actualizado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición DELETE para eliminar un estudiante
     */
    public function eliminar(int $id): array
    {
        try {
            $this->servicioEstudiantes->eliminar($id);
            
            return [
                'success' => true,
                'message' => 'Estudiante eliminado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para buscar estudiantes
     */
    public function buscar(string $termino): array
    {
        try {
            $estudiantes = $this->servicioEstudiantes->buscarPorNombre($termino);
            
            return [
                'success' => true,
                'data' => array_map(fn($estudiante) => $estudiante->toArray(), $estudiantes),
                'total' => count($estudiantes)
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
            $estadisticas = $this->servicioEstudiantes->obtenerEstadisticas();
            
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
     * Maneja la petición GET para obtener cumpleañeros
     */
    public function cumpleaneros(?string $fecha = null): array
    {
        try {
            $fechaBusqueda = $fecha ? new DateTime($fecha) : new DateTime();
            $cumpleaneros = $this->servicioEstudiantes->obtenerCumpleaneros($fechaBusqueda);
            
            return [
                'success' => true,
                'data' => array_map(fn($estudiante) => $estudiante->toArray(), $cumpleaneros),
                'total' => count($cumpleaneros)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida los datos para crear un estudiante
     */
    private function validarDatosCreacion(array $datos): array
    {
        $errores = [];
        
        if (empty($datos['dni'])) {
            $errores[] = 'El DNI es requerido';
        }
        
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        }
        
        if (empty($datos['apellido'])) {
            $errores[] = 'El apellido es requerido';
        }
        
        return $errores;
    }

    /**
     * Valida los datos para actualizar un estudiante
     */
    private function validarDatosActualizacion(array $datos): array
    {
        $errores = [];
        
        if (isset($datos['dni']) && empty($datos['dni'])) {
            $errores[] = 'El DNI no puede estar vacío';
        }
        
        if (isset($datos['nombre']) && empty($datos['nombre'])) {
            $errores[] = 'El nombre no puede estar vacío';
        }
        
        if (isset($datos['apellido']) && empty($datos['apellido'])) {
            $errores[] = 'El apellido no puede estar vacío';
        }
        
        return $errores;
    }
}
