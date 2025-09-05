<?php

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Nota;
use DateTime;

/**
 * Implementación concreta del NotaMapper
 * 
 * Conecta la nueva arquitectura con la base de datos existente
 * para la gestión de calificaciones.
 */
class NotaMapper
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function findById(int $id): ?Nota
    {
        $sql = "SELECT * FROM notas WHERE id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToNota($row);
    }

    public function findByEstudiante(int $estudianteId): array
    {
        $sql = "SELECT * FROM notas WHERE estudiante_id = ? ORDER BY fecha_registro DESC";
        $rows = $this->database->fetchAll($sql, [$estudianteId]);
        
        return array_map([$this, 'mapRowToNota'], $rows);
    }

    public function findByMateria(int $estudianteId, int $materiaId): array
    {
        $sql = "SELECT * FROM notas WHERE estudiante_id = ? AND materia_id = ? ORDER BY fecha_registro DESC";
        $rows = $this->database->fetchAll($sql, [$estudianteId, $materiaId]);
        
        return array_map([$this, 'mapRowToNota'], $rows);
    }

    public function findByBimestre(int $estudianteId, string $bimestre): array
    {
        $sql = "SELECT * FROM notas WHERE estudiante_id = ? AND cuatrimestre = ? ORDER BY fecha_registro DESC";
        $rows = $this->database->fetchAll($sql, [$estudianteId, $bimestre]);
        
        return array_map([$this, 'mapRowToNota'], $rows);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM notas ORDER BY fecha_registro DESC";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToNota'], $rows);
    }

    public function save(Nota $nota): Nota
    {
        $sql = "INSERT INTO notas (estudiante_id, materia_id, nota, cuatrimestre, observaciones, usuario_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $nota->getEstudianteId(),
            $nota->getMateriaId(),
            $nota->getValor(),
            $nota->getBimestre(),
            $nota->getObservaciones(),
            1 // usuario_id por defecto
        ];
        
        $this->database->query($sql, $params);
        $id = $this->database->lastInsertId();
        
        $nota->setId($id);
        return $nota;
    }

    public function update(Nota $nota): bool
    {
        $sql = "UPDATE notas SET nota = ?, observaciones = ? WHERE id = ?";
        
        $params = [
            $nota->getValor(),
            $nota->getObservaciones(),
            $nota->getId()
        ];
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM notas WHERE id = ?";
        $stmt = $this->database->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function getPromedioMateria(int $estudianteId, int $materiaId): float
    {
        $sql = "SELECT AVG(nota) as promedio FROM notas WHERE estudiante_id = ? AND materia_id = ?";
        $result = $this->database->fetch($sql, [$estudianteId, $materiaId]);
        
        return $result['promedio'] ? (float) $result['promedio'] : 0.0;
    }

    public function getPromedioGeneral(int $estudianteId): float
    {
        $sql = "SELECT AVG(nota) as promedio FROM notas WHERE estudiante_id = ?";
        $result = $this->database->fetch($sql, [$estudianteId]);
        
        return $result['promedio'] ? (float) $result['promedio'] : 0.0;
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
        
        $sql = "SELECT COUNT(*) as count FROM notas";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        return (int) $result['count'];
    }

    public function getEstadisticas(?int $materiaId = null, ?string $bimestre = null): array
    {
        $whereConditions = [];
        $params = [];
        
        if ($materiaId !== null) {
            $whereConditions[] = "materia_id = ?";
            $params[] = $materiaId;
        }
        
        if ($bimestre !== null) {
            $whereConditions[] = "cuatrimestre = ?";
            $params[] = $bimestre;
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_notas,
                    AVG(nota) as promedio_general,
                    MIN(nota) as nota_minima,
                    MAX(nota) as nota_maxima,
                    SUM(CASE WHEN nota >= 6 THEN 1 ELSE 0 END) as aprobados,
                    SUM(CASE WHEN nota < 6 THEN 1 ELSE 0 END) as desaprobados
                FROM notas";
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        
        return [
            'total_notas' => (int) $result['total_notas'],
            'promedio_general' => $result['promedio_general'] ? (float) $result['promedio_general'] : 0.0,
            'nota_minima' => $result['nota_minima'] ? (float) $result['nota_minima'] : 0.0,
            'nota_maxima' => $result['nota_maxima'] ? (float) $result['nota_maxima'] : 0.0,
            'aprobados' => (int) $result['aprobados'],
            'desaprobados' => (int) $result['desaprobados']
        ];
    }

    /**
     * Convierte una fila de la base de datos en un objeto Nota
     */
    private function mapRowToNota(array $row): Nota
    {
        $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $row['fecha_registro']);
        
        $nota = new Nota(
            (int) $row['estudiante_id'],
            (int) $row['materia_id'],
            (float) $row['nota'],
            (string) $row['cuatrimestre'],
            $row['observaciones'] ?? '',
            $fecha
        );
        
        // Establecer el ID
        $nota->setId((int) $row['id']);
        
        return $nota;
    }
}
