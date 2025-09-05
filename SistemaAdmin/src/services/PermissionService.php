<?php

namespace SistemaAdmin\Services;

/**
 * Servicio de Permisos
 * 
 * Maneja únicamente la lógica de permisos y roles
 */
class PermissionService
{
    private $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function tienePermiso(string $permiso): bool
    {
        $rol = $this->sessionService->obtenerRol();
        
        if (!$rol) {
            return false;
        }

        // Admin tiene todos los permisos
        if ($rol === 'admin') {
            return true;
        }

        // Definir permisos por rol
        $permisosPorRol = $this->obtenerPermisosPorRol();

        return isset($permisosPorRol[$rol]) && in_array($permiso, $permisosPorRol[$rol]);
    }

    /**
     * Verificar si el usuario tiene alguno de los permisos especificados
     */
    public function tieneAlgunPermiso(array $permisos): bool
    {
        foreach ($permisos as $permiso) {
            if ($this->tienePermiso($permiso)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verificar si el usuario tiene todos los permisos especificados
     */
    public function tieneTodosLosPermisos(array $permisos): bool
    {
        foreach ($permisos as $permiso) {
            if (!$this->tienePermiso($permiso)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtener todos los permisos del usuario actual
     */
    public function obtenerPermisosUsuario(): array
    {
        $rol = $this->sessionService->obtenerRol();
        
        if (!$rol) {
            return [];
        }

        // Admin tiene todos los permisos
        if ($rol === 'admin') {
            return $this->obtenerTodosLosPermisos();
        }

        return $this->obtenerPermisosPorRol()[$rol] ?? [];
    }

    /**
     * Verificar si el usuario puede acceder a una entidad específica
     */
    public function puedeAccederA(string $entidad, string $accion = 'ver'): bool
    {
        $permiso = $this->generarPermiso($entidad, $accion);
        return $this->tienePermiso($permiso);
    }

    /**
     * Verificar si el usuario puede modificar una entidad específica
     */
    public function puedeModificar(string $entidad): bool
    {
        return $this->puedeAccederA($entidad, 'modificar');
    }

    /**
     * Verificar si el usuario puede eliminar una entidad específica
     */
    public function puedeEliminar(string $entidad): bool
    {
        return $this->puedeAccederA($entidad, 'eliminar');
    }

    /**
     * Verificar si el usuario puede crear una entidad específica
     */
    public function puedeCrear(string $entidad): bool
    {
        return $this->puedeAccederA($entidad, 'crear');
    }

    /**
     * Obtener permisos por rol
     */
    private function obtenerPermisosPorRol(): array
    {
        return [
            'directivo' => [
                'ver_estudiantes',
                'ver_profesores',
                'ver_cursos',
                'ver_materias',
                'ver_especialidades',
                'ver_horarios',
                'ver_llamados',
                'ver_notas',
                'ver_equipo',
                'modificar_estudiantes',
                'modificar_profesores',
                'modificar_cursos',
                'modificar_materias',
                'modificar_especialidades',
                'modificar_horarios',
                'modificar_llamados',
                'modificar_notas',
                'modificar_equipo',
                'crear_estudiantes',
                'crear_profesores',
                'crear_cursos',
                'crear_materias',
                'crear_especialidades',
                'crear_horarios',
                'crear_llamados',
                'crear_notas',
                'crear_equipo',
                'eliminar_estudiantes',
                'eliminar_profesores',
                'eliminar_cursos',
                'eliminar_materias',
                'eliminar_especialidades',
                'eliminar_horarios',
                'eliminar_llamados',
                'eliminar_notas',
                'eliminar_equipo',
                'gestionar_usuarios',
                'ver_reportes',
                'exportar_datos'
            ],
            'profesor' => [
                'ver_estudiantes',
                'ver_cursos',
                'ver_materias',
                'ver_horarios',
                'ver_llamados',
                'ver_notas',
                'modificar_llamados',
                'modificar_notas',
                'crear_llamados',
                'crear_notas',
                'ver_mis_cursos',
                'ver_mis_materias',
                'ver_mis_horarios'
            ],
            'preceptor' => [
                'ver_estudiantes',
                'ver_cursos',
                'ver_horarios',
                'ver_llamados',
                'modificar_estudiantes',
                'modificar_llamados',
                'crear_llamados',
                'ver_asistencia'
            ],
            'secretario' => [
                'ver_estudiantes',
                'ver_profesores',
                'ver_cursos',
                'ver_materias',
                'ver_especialidades',
                'modificar_estudiantes',
                'modificar_profesores',
                'crear_estudiantes',
                'crear_profesores',
                'ver_reportes',
                'exportar_datos'
            ]
        ];
    }

    /**
     * Obtener todos los permisos disponibles
     */
    private function obtenerTodosLosPermisos(): array
    {
        $permisos = [];
        foreach ($this->obtenerPermisosPorRol() as $rolPermisos) {
            $permisos = array_merge($permisos, $rolPermisos);
        }
        return array_unique($permisos);
    }

    /**
     * Generar nombre de permiso basado en entidad y acción
     */
    private function generarPermiso(string $entidad, string $accion): string
    {
        return $accion . '_' . $entidad;
    }

    /**
     * Verificar si el usuario puede acceder a un recurso específico
     */
    public function puedeAccederARecurso(string $recurso): bool
    {
        $permisosRecurso = [
            'estudiantes' => 'ver_estudiantes',
            'profesores' => 'ver_profesores',
            'cursos' => 'ver_cursos',
            'materias' => 'ver_materias',
            'especialidades' => 'ver_especialidades',
            'horarios' => 'ver_horarios',
            'llamados' => 'ver_llamados',
            'notas' => 'ver_notas',
            'equipo' => 'ver_equipo',
            'reportes' => 'ver_reportes'
        ];

        $permiso = $permisosRecurso[$recurso] ?? null;
        return $permiso ? $this->tienePermiso($permiso) : false;
    }

    /**
     * Obtener roles disponibles
     */
    public function obtenerRolesDisponibles(): array
    {
        return ['admin', 'directivo', 'profesor', 'preceptor', 'secretario'];
    }

    /**
     * Verificar si un rol es válido
     */
    public function esRolValido(string $rol): bool
    {
        return in_array($rol, $this->obtenerRolesDisponibles());
    }
}
