<?php

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Interfaces\IEstudianteMapper;
use SistemaAdmin\Models\Estudiante;
use DateTime;

/**
 * Implementación concreta del EstudianteMapper
 * 
 * Conecta la nueva arquitectura con la base de datos existente
 * sin modificar la estructura de tablas actual.
 */
class EstudianteMapper implements IEstudianteMapper
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function findById(int $id): ?Estudiante
    {
        $sql = "SELECT * FROM estudiantes WHERE id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToEstudiante($row);
    }

    public function findByDni(string $dni): ?Estudiante
    {
        $sql = "SELECT * FROM estudiantes WHERE dni = ?";
        $row = $this->database->fetch($sql, [$dni]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToEstudiante($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM estudiantes ORDER BY apellido, nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToEstudiante'], $rows);
    }

    public function findActive(): array
    {
        $sql = "SELECT * FROM estudiantes WHERE activo = 1 ORDER BY apellido, nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToEstudiante'], $rows);
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
        
        $sql = "SELECT * FROM estudiantes";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        $sql .= " ORDER BY apellido, nombre";
        
        $rows = $this->database->fetchAll($sql, $params);
        
        return array_map([$this, 'mapRowToEstudiante'], $rows);
    }

    public function save(Estudiante $estudiante): Estudiante
    {
        $sql = "INSERT INTO estudiantes (dni, apellido, nombre, fecha_nacimiento, grupo_sanguineo, 
                obra_social, domicilio, telefono_fijo, telefono_celular, email, curso_id, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $estudiante->getDni(),
            $estudiante->getApellido(),
            $estudiante->getNombre(),
            $estudiante->getFechaNacimiento()?->format('Y-m-d'),
            $estudiante->getGrupoSanguineo(),
            $estudiante->getObraSocial(),
            $estudiante->getDomicilio(),
            $estudiante->getTelefonoFijo(),
            $estudiante->getTelefonoCelular(),
            $estudiante->getEmail(),
            $estudiante->getCursoId(),
            $estudiante->esActivo() ? 1 : 0
        ];
        
        $this->database->query($sql, $params);
        $id = $this->database->lastInsertId();
        
        $estudiante->setId($id);
        return $estudiante;
    }

    public function update(Estudiante $estudiante): bool
    {
        $sql = "UPDATE estudiantes SET dni = ?, apellido = ?, nombre = ?, fecha_nacimiento = ?, 
                grupo_sanguineo = ?, obra_social = ?, domicilio = ?, telefono_fijo = ?, 
                telefono_celular = ?, email = ?, curso_id = ?, activo = ? WHERE id = ?";
        
        $params = [
            $estudiante->getDni(),
            $estudiante->getApellido(),
            $estudiante->getNombre(),
            $estudiante->getFechaNacimiento()?->format('Y-m-d'),
            $estudiante->getGrupoSanguineo(),
            $estudiante->getObraSocial(),
            $estudiante->getDomicilio(),
            $estudiante->getTelefonoFijo(),
            $estudiante->getTelefonoCelular(),
            $estudiante->getEmail(),
            $estudiante->getCursoId(),
            $estudiante->esActivo() ? 1 : 0,
            $estudiante->getId()
        ];
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        // Soft delete - marcamos como inactivo en lugar de eliminar
        $sql = "UPDATE estudiantes SET activo = 0 WHERE id = ?";
        $stmt = $this->database->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function existsByDni(string $dni, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM estudiantes WHERE dni = ?";
        $params = [$dni];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->database->fetch($sql, $params);
        return $result['count'] > 0;
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
        
        $sql = "SELECT COUNT(*) as count FROM estudiantes";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        return (int) $result['count'];
    }

    public function findWithPagination(int $offset, int $limit, array $criteria = []): array
    {
        $whereConditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $whereConditions[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        $sql = "SELECT * FROM estudiantes";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        $sql .= " ORDER BY apellido, nombre LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $rows = $this->database->fetchAll($sql, $params);
        
        return array_map([$this, 'mapRowToEstudiante'], $rows);
    }

    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Convierte una fila de la base de datos en un objeto Estudiante
     */
    private function mapRowToEstudiante(array $row): Estudiante
    {
        $fechaNacimiento = null;
        if (!empty($row['fecha_nacimiento'])) {
            $fechaNacimiento = DateTime::createFromFormat('Y-m-d', $row['fecha_nacimiento']);
        }
        
        $estudiante = new Estudiante(
            $row['dni'],
            $row['nombre'],
            $row['apellido'],
            $fechaNacimiento,
            $row['grupo_sanguineo'],
            $row['obra_social'],
            $row['domicilio'],
            $row['telefono_fijo'],
            $row['telefono_celular'],
            $row['email'],
            $row['curso_id'],
            (bool) $row['activo']
        );
        
        // Establecer el ID
        $estudiante->setId((int) $row['id']);
        
        return $estudiante;
    }
}
