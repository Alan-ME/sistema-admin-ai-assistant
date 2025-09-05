<?php

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Services\ServicioAutenticacion;

/**
 * Controller para manejar las peticiones HTTP relacionadas con horarios
 * 
 * Este controller actúa como intermediario entre la capa de presentación
 * y los servicios de lógica de negocio para horarios.
 */
class HorariosController
{
    private ServicioAutenticacion $servicioAutenticacion;
    private $database;

    public function __construct(ServicioAutenticacion $servicioAutenticacion, $database)
    {
        $this->servicioAutenticacion = $servicioAutenticacion;
        $this->database = $database;
    }

    /**
     * Maneja la petición GET para listar horarios
     */
    public function listar(array $filtros = []): array
    {
        try {
            // Construir consulta con filtros
            $where_conditions = ["1=1"];
            $params = [];

            if (!empty($filtros['curso'])) {
                $where_conditions[] = "h.curso_id = ?";
                $params[] = $filtros['curso'];
            }

            if (!empty($filtros['profesor'])) {
                $where_conditions[] = "h.docente = ?";
                $params[] = $filtros['profesor'];
            }

            $where_clause = implode(" AND ", $where_conditions);

            // Obtener horarios con información de suplencias
            $horarios = $this->database->fetchAll("
                SELECT h.*, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno,
                       m.nombre as materia,
                       s.estado as suplencia_estado, s.fuera_servicio,
                       sup.apellido as suplente_apellido, sup.nombre as suplente_nombre
                FROM horarios h
                JOIN cursos c ON h.curso_id = c.id
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                LEFT JOIN turnos t ON c.turno_id = t.id
                LEFT JOIN materias m ON h.materia_id = m.id
                LEFT JOIN suplencias s ON h.materia_id = s.materia_id AND s.estado = 'activa'
                LEFT JOIN suplentes sup ON s.suplente_id = sup.id
                WHERE $where_clause AND c.activo = 1
                ORDER BY c.anio, c.division, h.dia_semana, h.hora_inicio
            ", $params);

            return [
                'success' => true,
                'data' => $horarios,
                'total' => count($horarios)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener un horario por ID
     */
    public function obtener(int $horarioId): array
    {
        try {
            $horario = $this->database->fetch("
                SELECT h.*, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno,
                       m.nombre as materia
                FROM horarios h
                JOIN cursos c ON h.curso_id = c.id
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                LEFT JOIN turnos t ON c.turno_id = t.id
                LEFT JOIN materias m ON h.materia_id = m.id
                WHERE h.id = ?
            ", [$horarioId]);
            
            if (!$horario) {
                return [
                    'success' => false,
                    'error' => 'Horario no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'data' => $horario
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición POST para crear un nuevo horario
     */
    public function crear(array $datos): array
    {
        try {
            // Verificar permisos
            if (!$this->servicioAutenticacion->tienePermiso('gestionar_horarios')) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para crear horarios'
                ];
            }
            
            // Validar datos requeridos
            $errores = $this->validarDatosHorario($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Insertar horario
            $sql = "INSERT INTO horarios (curso_id, materia_id, dia_semana, hora_inicio, hora_fin, aula, docente, es_contraturno) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $datos['curso_id'],
                $datos['materia_id'],
                $datos['dia_semana'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['aula'] ?? null,
                $datos['docente'] ?? null,
                isset($datos['es_contraturno']) ? 1 : 0
            ];
            
            $this->database->query($sql, $params);
            $horarioId = $this->database->lastInsertId();
            
            return [
                'success' => true,
                'data' => ['id' => $horarioId],
                'message' => 'Horario creado exitosamente'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al crear horario: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición PUT para actualizar un horario
     */
    public function actualizar(int $horarioId, array $datos): array
    {
        try {
            // Verificar permisos
            if (!$this->servicioAutenticacion->tienePermiso('gestionar_horarios')) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para modificar horarios'
                ];
            }
            
            // Validar datos requeridos
            $errores = $this->validarDatosHorario($datos);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'errors' => $errores
                ];
            }
            
            // Actualizar horario
            $sql = "UPDATE horarios SET 
                    curso_id = ?, materia_id = ?, dia_semana = ?, hora_inicio = ?, 
                    hora_fin = ?, aula = ?, docente = ?, es_contraturno = ? 
                    WHERE id = ?";
            
            $params = [
                $datos['curso_id'],
                $datos['materia_id'],
                $datos['dia_semana'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['aula'] ?? null,
                $datos['docente'] ?? null,
                isset($datos['es_contraturno']) ? 1 : 0,
                $horarioId
            ];
            
            $stmt = $this->database->query($sql, $params);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'data' => ['id' => $horarioId],
                    'message' => 'Horario actualizado exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Horario no encontrado'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al actualizar horario: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición DELETE para eliminar un horario
     */
    public function eliminar(int $horarioId): array
    {
        try {
            // Verificar permisos
            if (!$this->servicioAutenticacion->tienePermiso('gestionar_horarios')) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para eliminar horarios'
                ];
            }
            
            // Eliminar horario
            $stmt = $this->database->query("DELETE FROM horarios WHERE id = ?", [$horarioId]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Horario eliminado exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Horario no encontrado'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al eliminar horario: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja la petición GET para obtener datos para formularios
     */
    public function obtenerDatosFormularios(): array
    {
        try {
            // Obtener cursos
            $cursos = $this->database->fetchAll("
                SELECT c.id, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno
                FROM cursos c
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                LEFT JOIN turnos t ON c.turno_id = t.id
                WHERE c.activo = 1
                ORDER BY c.anio, c.division
            ");
            
            // Obtener materias
            $materias = $this->database->fetchAll("
                SELECT * FROM materias WHERE activa = 1 ORDER BY nombre
            ");
            
            // Obtener profesores para filtro
            $profesores = $this->database->fetchAll("
                SELECT DISTINCT h.docente
                FROM horarios h
                WHERE h.docente IS NOT NULL AND h.docente != ''
                ORDER BY h.docente
            ");
            
            return [
                'success' => true,
                'data' => [
                    'cursos' => $cursos,
                    'materias' => $materias,
                    'profesores' => $profesores
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
     * Maneja la petición GET para obtener profesores por materia y curso
     */
    public function obtenerProfesoresPorMateria(int $materiaId, int $cursoId): array
    {
        try {
            $profesores = $this->database->fetchAll("
                SELECT DISTINCT p.id, p.apellido, p.nombre
                FROM profesores p
                JOIN profesor_materia pm ON p.id = pm.profesor_id
                JOIN profesor_curso pc ON p.id = pc.profesor_id
                WHERE pm.materia_id = ? 
                AND pc.curso_id = ? 
                AND pm.activo = 1 
                AND pc.activo = 1 
                AND p.activo = 1
                AND pc.anio_academico = YEAR(CURDATE())
                AND NOT EXISTS (
                    SELECT 1 FROM profesor_materia pm2
                    JOIN profesor_curso pc2 ON pm2.profesor_id = pc2.profesor_id
                    WHERE pm2.materia_id = pm.materia_id
                    AND pm2.profesor_id != p.id
                    AND pm2.activo = 1
                    AND pc2.curso_id = pc.curso_id
                    AND pc2.anio_academico = pc.anio_academico
                    AND pc2.activo = 1
                )
                ORDER BY p.apellido, p.nombre
            ", [$materiaId, $cursoId]);
            
            return [
                'success' => true,
                'data' => $profesores
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida los datos de un horario
     */
    private function validarDatosHorario(array $datos): array
    {
        $errores = [];
        
        if (empty($datos['curso_id'])) {
            $errores[] = 'El curso es requerido';
        }
        
        if (empty($datos['materia_id'])) {
            $errores[] = 'La materia es requerida';
        }
        
        if (empty($datos['dia_semana'])) {
            $errores[] = 'El día de la semana es requerido';
        }
        
        if (empty($datos['hora_inicio'])) {
            $errores[] = 'La hora de inicio es requerida';
        }
        
        if (empty($datos['hora_fin'])) {
            $errores[] = 'La hora de fin es requerida';
        }
        
        // Validar que la hora de fin sea mayor que la de inicio
        if (!empty($datos['hora_inicio']) && !empty($datos['hora_fin'])) {
            if (strtotime($datos['hora_fin']) <= strtotime($datos['hora_inicio'])) {
                $errores[] = 'La hora de fin debe ser mayor que la hora de inicio';
            }
        }
        
        // Validar día de la semana
        if (!empty($datos['dia_semana']) && !in_array($datos['dia_semana'], [1, 2, 3, 4, 5, 6])) {
            $errores[] = 'El día de la semana debe ser entre 1 (Lunes) y 6 (Sábado)';
        }
        
        return $errores;
    }
}
