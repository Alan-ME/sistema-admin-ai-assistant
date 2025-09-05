<?php

namespace SistemaAdmin\Services;

/**
 * Repositorio de Usuarios
 * 
 * Maneja únicamente las operaciones de persistencia de usuarios
 */
class UsuarioRepository
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * Buscar usuario por username
     */
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT * FROM usuarios WHERE username = ? AND activo = 1";
        return $this->database->fetch($sql, [$username]);
    }

    /**
     * Buscar usuario por ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM usuarios WHERE id = ? AND activo = 1";
        return $this->database->fetch($sql, [$id]);
    }

    /**
     * Actualizar último acceso
     */
    public function actualizarUltimoAcceso(int $usuarioId): bool
    {
        try {
            $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
            $stmt = $this->database->query($sql, [$usuarioId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            // Si la columna ultimo_acceso no existe, no es crítico
            return true;
        }
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword(int $usuarioId, string $nuevaPassword): bool
    {
        $sql = "UPDATE usuarios SET password = ? WHERE id = ?";
        $stmt = $this->database->query($sql, [$nuevaPassword, $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Verificar contraseña actual
     */
    public function verificarPassword(int $usuarioId, string $password): bool
    {
        $usuario = $this->findById($usuarioId);
        if (!$usuario) {
            return false;
        }

        return password_verify($password, $usuario['password']);
    }

    /**
     * Crear nuevo usuario
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO usuarios (username, password, nombre, apellido, email, rol, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->database->query($sql, [
            $datos['username'],
            $datos['password'],
            $datos['nombre'],
            $datos['apellido'],
            $datos['email'],
            $datos['rol'],
            $datos['activo'] ?? 1
        ]);

        return $this->database->lastInsertId();
    }

    /**
     * Actualizar usuario
     */
    public function actualizar(int $usuarioId, array $datos): bool
    {
        $campos = [];
        $valores = [];

        foreach ($datos as $campo => $valor) {
            if (in_array($campo, ['username', 'nombre', 'apellido', 'email', 'rol', 'activo'])) {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
            }
        }

        if (empty($campos)) {
            return false;
        }

        $valores[] = $usuarioId;
        $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
        
        $stmt = $this->database->query($sql, $valores);
        return $stmt->rowCount() > 0;
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function eliminar(int $usuarioId): bool
    {
        $sql = "UPDATE usuarios SET activo = 0 WHERE id = ?";
        $stmt = $this->database->query($sql, [$usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Listar usuarios
     */
    public function listar(int $limite = 50, int $offset = 0): array
    {
        $sql = "SELECT id, username, nombre, apellido, email, rol, activo, ultimo_acceso 
                FROM usuarios 
                ORDER BY nombre, apellido 
                LIMIT ? OFFSET ?";
        
        return $this->database->fetchAll($sql, [$limite, $offset]);
    }

    /**
     * Contar usuarios
     */
    public function contar(): int
    {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1";
        $resultado = $this->database->fetch($sql);
        return (int) $resultado['total'];
    }
}
