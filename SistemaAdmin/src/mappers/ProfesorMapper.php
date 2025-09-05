<?php

namespace SistemaAdmin\Mappers;

use SistemaAdmin\Models\Profesor;

/**
 * Implementación concreta del ProfesorMapper
 * 
 * Conecta la nueva arquitectura con la base de datos existente
 * para la gestión de profesores.
 */
class ProfesorMapper
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function findById(int $id): ?Profesor
    {
        $sql = "SELECT * FROM profesores WHERE id = ?";
        $row = $this->database->fetch($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToProfesor($row);
    }

    public function findByDni(string $dni): ?Profesor
    {
        $sql = "SELECT * FROM profesores WHERE dni = ?";
        $row = $this->database->fetch($sql, [$dni]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToProfesor($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM profesores ORDER BY apellido, nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function findActive(): array
    {
        $sql = "SELECT * FROM profesores WHERE activo = 1 ORDER BY apellido, nombre";
        $rows = $this->database->fetchAll($sql);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
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
        
        $sql = "SELECT * FROM profesores";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        $sql .= " ORDER BY apellido, nombre";
        
        $rows = $this->database->fetchAll($sql, $params);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function save(Profesor $profesor): Profesor
    {
        $sql = "INSERT INTO profesores (dni, apellido, nombre, fecha_nacimiento, domicilio, 
                telefono_fijo, telefono_celular, email, titulo, especialidad, fecha_ingreso, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $profesor->getDni(),
            $profesor->getApellido(),
            $profesor->getNombre(),
            $profesor->getFechaNacimiento()?->format('Y-m-d'),
            $profesor->getDomicilio(),
            $profesor->getTelefonoFijo(),
            $profesor->getTelefonoCelular(),
            $profesor->getEmail(),
            $profesor->getTitulo(),
            $profesor->getEspecialidad(),
            $profesor->getFechaIngreso()?->format('Y-m-d'),
            $profesor->esActivo() ? 1 : 0
        ];
        
        $this->database->query($sql, $params);
        $id = $this->database->lastInsertId();
        
        $profesor->setId($id);
        return $profesor;
    }

    public function update(Profesor $profesor): bool
    {
        $sql = "UPDATE profesores SET dni = ?, apellido = ?, nombre = ?, fecha_nacimiento = ?, 
                domicilio = ?, telefono_fijo = ?, telefono_celular = ?, email = ?, titulo = ?, 
                especialidad = ?, fecha_ingreso = ?, activo = ? WHERE id = ?";
        
        $params = [
            $profesor->getDni(),
            $profesor->getApellido(),
            $profesor->getNombre(),
            $profesor->getFechaNacimiento()?->format('Y-m-d'),
            $profesor->getDomicilio(),
            $profesor->getTelefonoFijo(),
            $profesor->getTelefonoCelular(),
            $profesor->getEmail(),
            $profesor->getTitulo(),
            $profesor->getEspecialidad(),
            $profesor->getFechaIngreso()?->format('Y-m-d'),
            $profesor->esActivo() ? 1 : 0,
            $profesor->getId()
        ];
        
        $stmt = $this->database->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        // Soft delete - marcamos como inactivo
        $sql = "UPDATE profesores SET activo = 0 WHERE id = ?";
        $stmt = $this->database->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function existsByDni(string $dni, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM profesores WHERE dni = ?";
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
        
        $sql = "SELECT COUNT(*) as count FROM profesores";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->database->fetch($sql, $params);
        return (int) $result['count'];
    }

        public function findByNombre(string $nombre): array
    {
        $sql = "SELECT * FROM profesores WHERE activo = 1 AND 
                (nombre LIKE ? OR apellido LIKE ? OR dni LIKE ?) 
                ORDER BY apellido, nombre";
        $searchTerm = "%$nombre%";
        $rows = $this->database->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function findByEspecialidad(string $especialidad): array
    {
        $sql = "SELECT * FROM profesores WHERE activo = 1 AND especialidad LIKE ? 
                ORDER BY apellido, nombre";
        $searchTerm = "%$especialidad%";
        $rows = $this->database->fetchAll($sql, [$searchTerm]);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function findByMateria(int $materiaId): array
    {
        $sql = "SELECT DISTINCT p.* FROM profesores p
                JOIN profesor_materia pm ON p.id = pm.profesor_id
                WHERE p.activo = 1 AND pm.materia_id = ? AND pm.activo = 1
                ORDER BY p.apellido, p.nombre";
        $rows = $this->database->fetchAll($sql, [$materiaId]);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function findByMateriaYCurso(int $materiaId, int $cursoId): array
    {
        $sql = "SELECT DISTINCT p.* FROM profesores p
                JOIN profesor_materia pm ON p.id = pm.profesor_id
                JOIN profesor_curso pc ON p.id = pc.profesor_id
                WHERE p.activo = 1 AND pm.materia_id = ? AND pc.curso_id = ? 
                AND pm.activo = 1 AND pc.activo = 1
                ORDER BY p.apellido, p.nombre";
        $rows = $this->database->fetchAll($sql, [$materiaId, $cursoId]);
        
        return array_map([$this, 'mapRowToProfesor'], $rows);
    }

    public function tieneCursosAsignados(int $profesorId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM profesor_curso 
                WHERE profesor_id = ? AND activo = 1";
        $result = $this->database->fetch($sql, [$profesorId]);
        
        return $result['count'] > 0;
    }

    public function getCursosAsignados(int $profesorId): array
    {
        $sql = "SELECT c.*, esp.nombre as especialidad_nombre 
                FROM cursos c
                JOIN profesor_curso pc ON c.id = pc.curso_id
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                WHERE pc.profesor_id = ? AND pc.activo = 1
                ORDER BY c.anio, c.division";
        $rows = $this->database->fetchAll($sql, [$profesorId]);
        
        return $rows;
    }

    public function getMateriasAsignadas(int $profesorId): array
    {
        $sql = "SELECT m.* FROM materias m
                JOIN profesor_materia pm ON m.id = pm.materia_id
                WHERE pm.profesor_id = ? AND pm.activo = 1
                ORDER BY m.nombre";
        $rows = $this->database->fetchAll($sql, [$profesorId]);
        
        return $rows;
    }

    /**
     * Convierte una fila de la base de datos en un objeto Profesor
     */
    private function mapRowToProfesor(array $row): Profesor
    {
        $fechaNacimiento = !empty($row['fecha_nacimiento']) ? 
            \DateTime::createFromFormat('Y-m-d', $row['fecha_nacimiento']) : null;
        
        $fechaIngreso = !empty($row['fecha_ingreso']) ? 
            \DateTime::createFromFormat('Y-m-d', $row['fecha_ingreso']) : null;
        
        $profesor = new Profesor(
            $row['dni'],
            $row['apellido'],
            $row['nombre'],
            $fechaNacimiento,
            $row['domicilio'] ?? null,
            $row['telefono_fijo'] ?? null,
            $row['telefono_celular'] ?? null,
            $row['email'] ?? null,
            $row['titulo'] ?? null,
            $row['especialidad'] ?? null,
            $fechaIngreso,
            (bool) $row['activo']
        );
        
        // Establecer el ID
        $profesor->setId((int) $row['id']);
        
        return $profesor;
    }
}
