<?php

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Materia;

/**
 * Implementación concreta del MateriaMapper
 * 
 * Conecta la nueva arquitectura con la base de datos existente
 * para la gestión de materias.
 */
class MateriaMapper
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function findById(int $id): ?Materia
    {
        $sql = "SELECT * FROM materias WHERE id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToMateria($row);
    }

    public function findByCodigo(string $codigo): ?Materia
    {
        $sql = "SELECT * FROM materias WHERE codigo = ?";
        $row = $this->database->fetch($sql, [$codigo]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToMateria($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM materias ORDER BY nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function findActive(): array
    {
        $sql = "SELECT * FROM materias WHERE activo = 1 ORDER BY nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function findByEspecialidad(int $especialidadId): array
    {
        $sql = "SELECT * FROM materias WHERE especialidad_id = ? AND activo = 1 ORDER BY nombre";
        $rows = $this->database->fetchAll($sql, [$especialidadId]);
        
        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function findComunes(): array
    {
        $sql = "SELECT * FROM materias WHERE especialidad_id IS NULL AND activo = 1 ORDER BY nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function findByNombre(string $termino): array
    {
        $sql = "SELECT * FROM materias WHERE nombre LIKE ? AND activo = 1 ORDER BY nombre";
        $rows = $this->database->fetchAll($sql, ["%$termino%"]);
        
        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function findBy(array $criteria): array
    {
        $whereConditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $whereConditions[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        $sql = "SELECT * FROM materias";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        $sql .= " ORDER BY nombre";
        
        $rows = $this->database->fetchAll($sql, $params);
        
        return array_map([$this, 'mapRowToMateria'], $rows);
    }

    public function save(Materia $materia): Materia
    {
        $sql = "INSERT INTO materias (nombre, codigo, horas_semanales, especialidad_id, activo) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $materia->getNombre(),
            $materia->getCodigo(),
            $materia->getHorasSemanales(),
            $materia->getEspecialidadId(),
            $materia->esActiva() ? 1 : 0
        ];
        
        $this->database->query($sql, $params);
        $id = $this->database->lastInsertId();
        
        $materia->setId($id);
        return $materia;
    }

    public function update(Materia $materia): bool
    {
        $sql = "UPDATE materias SET nombre = ?, codigo = ?, horas_semanales = ?, especialidad_id = ?, activo = ? WHERE id = ?";
        
        $params = [
            $materia->getNombre(),
            $materia->getCodigo(),
            $materia->getHorasSemanales(),
            $materia->getEspecialidadId(),
            $materia->esActiva() ? 1 : 0,
            $materia->getId()
        ];
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        // Soft delete - marcamos como inactiva
        $sql = "UPDATE materias SET activo = 0 WHERE id = ?";
        $stmt = $this->database->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function codigoExiste(string $codigo, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM materias WHERE codigo = ?";
        $params = [$codigo];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->database->fetch($sql, $params);
        return $result['count'] > 0;
    }

    public function findByCargaHoraria(int $horasMinimas, int $horasMaximas): array
    {
        $sql = "SELECT * FROM materias WHERE horas_semanales >= ? AND horas_semanales <= ? AND activo = 1 ORDER BY nombre";
        $rows = $this->database->fetchAll($sql, [$horasMinimas, $horasMaximas]);
        
        return array_map([$this, 'mapRowToMateria'], $rows);
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
        
        $sql = "SELECT COUNT(*) as count FROM materias";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        return (int) $result['count'];
    }

    public function getEstadisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_materias,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as materias_activas,
                    SUM(CASE WHEN especialidad_id IS NULL THEN 1 ELSE 0 END) as materias_comunes,
                    SUM(CASE WHEN especialidad_id IS NOT NULL THEN 1 ELSE 0 END) as materias_especificas,
                    AVG(horas_semanales) as promedio_horas_semanales
                FROM materias";
        
        $result = $this->database->fetch($sql);
        
        return [
            'total_materias' => (int) $result['total_materias'],
            'materias_activas' => (int) $result['materias_activas'],
            'materias_comunes' => (int) $result['materias_comunes'],
            'materias_especificas' => (int) $result['materias_especificas'],
            'promedio_horas_semanales' => $result['promedio_horas_semanales'] ? (float) $result['promedio_horas_semanales'] : 0.0
        ];
    }

    /**
     * Convierte una fila de la base de datos en un objeto Materia
     */
    private function mapRowToMateria(array $row): Materia
    {
        $materia = new Materia(
            $row['nombre'],
            $row['codigo'],
            $row['horas_semanales'],
            $row['especialidad_id'],
            (bool) $row['activo']
        );
        
        // Establecer el ID
        $materia->setId((int) $row['id']);
        
        return $materia;
    }
}
