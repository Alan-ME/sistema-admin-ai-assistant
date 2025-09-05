<?php

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\LlamadoAtencion;
use DateTime;

/**
 * Implementación concreta del LlamadoMapper
 * 
 * Conecta la nueva arquitectura con la base de datos existente
 * para la gestión de llamados de atención.
 */
class LlamadoMapper
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function findById(int $id): ?LlamadoAtencion
    {
        $sql = "SELECT * FROM llamados_atencion WHERE id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToLlamado($row);
    }

    public function findByEstudiante(int $estudianteId): array
    {
        $sql = "SELECT * FROM llamados_atencion WHERE estudiante_id = ? ORDER BY fecha DESC";
        $rows = $this->database->fetchAll($sql, [$estudianteId]);
        
        return array_map([$this, 'mapRowToLlamado'], $rows);
    }

    public function findRecientes(int $dias = 7): array
    {
        $sql = "SELECT l.*, e.apellido, e.nombre, e.dni, c.anio, c.division, esp.nombre as especialidad, c.id as curso_id,
                       u.apellido as usuario_apellido, u.nombre as usuario_nombre, l.fecha_registro
                FROM llamados_atencion l
                JOIN estudiantes e ON l.estudiante_id = e.id
                LEFT JOIN cursos c ON e.curso_id = c.id
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE l.fecha >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                ORDER BY l.fecha DESC";
        $rows = $this->database->fetchAll($sql, [$dias]);
        
        return array_map([$this, 'mapRowToLlamadoWithDetails'], $rows);
    }

    public function findByPeriodo(DateTime $fechaInicio, DateTime $fechaFin): array
    {
        $sql = "SELECT * FROM llamados_atencion WHERE fecha BETWEEN ? AND ? ORDER BY fecha DESC";
        $rows = $this->database->fetchAll($sql, [
            $fechaInicio->format('Y-m-d'),
            $fechaFin->format('Y-m-d')
        ]);
        
        return array_map([$this, 'mapRowToLlamado'], $rows);
    }

    public function findGraves(): array
    {
        $sql = "SELECT * FROM llamados_atencion WHERE 
                (motivo LIKE '%agresión%' OR motivo LIKE '%violencia%' OR motivo LIKE '%drogas%' 
                 OR motivo LIKE '%alcohol%' OR motivo LIKE '%robo%' OR motivo LIKE '%hurto%'
                 OR motivo LIKE '%amenaza%' OR motivo LIKE '%acoso%' OR motivo LIKE '%discriminación%'
                 OR motivo LIKE '%vandalismo%') 
                ORDER BY fecha DESC";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToLlamado'], $rows);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM llamados_atencion ORDER BY fecha DESC";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToLlamado'], $rows);
    }

    public function save(LlamadoAtencion $llamado): LlamadoAtencion
    {
        $sql = "INSERT INTO llamados_atencion (estudiante_id, motivo, observaciones, sancion, usuario_id, fecha) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $llamado->getEstudianteId(),
            $llamado->getMotivo(),
            $llamado->getDescripcion(),
            $llamado->getSancion(),
            $llamado->getUsuarioId(),
            $llamado->getFecha()->format('Y-m-d')
        ];
        
        $this->database->query($sql, $params);
        $id = $this->database->lastInsertId();
        
        $llamado->setId($id);
        return $llamado;
    }

    public function update(LlamadoAtencion $llamado): bool
    {
        $sql = "UPDATE llamados_atencion SET sancion = ? WHERE id = ?";
        
        $params = [
            $llamado->getSancion(),
            $llamado->getId()
        ];
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM llamados_atencion WHERE id = ?";
        $stmt = $this->database->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function countBy(array $criteria): int
    {
        $whereConditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $whereConditions[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        $sql = "SELECT COUNT(*) as count FROM llamados_atencion";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        return (int) $result['count'];
    }

    public function getEstadisticas(?int $estudianteId = null, ?DateTime $fechaInicio = null, ?DateTime $fechaFin = null): array
    {
        $whereConditions = [];
        $params = [];
        
        if ($estudianteId !== null) {
            $whereConditions[] = "estudiante_id = ?";
            $params[] = $estudianteId;
        }
        
        if ($fechaInicio !== null) {
            $whereConditions[] = "fecha >= ?";
            $params[] = $fechaInicio->format('Y-m-d');
        }
        
        if ($fechaFin !== null) {
            $whereConditions[] = "fecha <= ?";
            $params[] = $fechaFin->format('Y-m-d');
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_llamados,
                    SUM(CASE WHEN motivo LIKE '%agresión%' OR motivo LIKE '%violencia%' OR motivo LIKE '%drogas%' 
                              OR motivo LIKE '%alcohol%' OR motivo LIKE '%robo%' OR motivo LIKE '%hurto%'
                              OR motivo LIKE '%amenaza%' OR motivo LIKE '%acoso%' OR motivo LIKE '%discriminación%'
                              OR motivo LIKE '%vandalismo%' THEN 1 ELSE 0 END) as graves,
                    SUM(CASE WHEN sancion IS NOT NULL AND sancion != '' THEN 1 ELSE 0 END) as con_sancion
                FROM llamados_atencion";
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        
        return [
            'total_llamados' => (int) $result['total_llamados'],
            'graves' => (int) $result['graves'],
            'con_sancion' => (int) $result['con_sancion']
        ];
    }

    public function tieneLlamadosRecientes(int $estudianteId, int $dias = 30): bool
    {
        $sql = "SELECT COUNT(*) as count FROM llamados_atencion 
                WHERE estudiante_id = ? AND fecha >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $result = $this->database->fetch($sql, [$estudianteId, $dias]);
        
        return $result['count'] > 0;
    }

    /**
     * Convierte una fila de la base de datos en un objeto LlamadoAtencion
     */
    private function mapRowToLlamado(array $row): LlamadoAtencion
    {
        $fecha = DateTime::createFromFormat('Y-m-d', $row['fecha']);
        
        $llamado = new LlamadoAtencion(
            (int) $row['estudiante_id'],
            $row['motivo'],
            $row['observaciones'] ?? '', // Usar observaciones en lugar de descripcion
            (int) $row['usuario_id'],
            $row['sancion'],
            $fecha
        );
        
        // Establecer el ID
        $llamado->setId((int) $row['id']);
        
        return $llamado;
    }

    /**
     * Convierte una fila de la base de datos en un array con detalles del estudiante y curso
     */
    private function mapRowToLlamadoWithDetails(array $row): array
    {
        $fecha = DateTime::createFromFormat('Y-m-d', $row['fecha']);
        
        $llamado = new LlamadoAtencion(
            (int) $row['estudiante_id'],
            $row['motivo'],
            $row['observaciones'] ?? '',
            (int) $row['usuario_id'],
            $row['sancion'],
            $fecha
        );
        
        // Establecer el ID
        $llamado->setId((int) $row['id']);
        
        // Convertir a array y agregar información adicional
        $llamadoArray = $llamado->toArray();
        
        // Agregar información del estudiante y curso
        $llamadoArray['apellido'] = $row['apellido'];
        $llamadoArray['nombre'] = $row['nombre'];
        $llamadoArray['dni'] = $row['dni'];
        $llamadoArray['anio'] = $row['anio'];
        $llamadoArray['division'] = $row['division'];
        $llamadoArray['especialidad'] = $row['especialidad'];
        $llamadoArray['curso_id'] = $row['curso_id'];
        $llamadoArray['usuario_apellido'] = $row['usuario_apellido'];
        $llamadoArray['usuario_nombre'] = $row['usuario_nombre'];
        $llamadoArray['fecha_registro'] = $row['fecha_registro'];
        
        return $llamadoArray;
    }
}
