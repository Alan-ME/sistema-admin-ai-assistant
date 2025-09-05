"""
Consultas SQL predefinidas para SistemaAdmin AI
Optimizadas para el plan gratuito de Render
"""

class SistemaAdminQueries:
    """Clase con consultas SQL predefinidas para el sistema"""
    
    # Consultas de estudiantes
    GET_ALL_STUDENTS = """
    SELECT 
        e.id,
        e.dni,
        e.nombre,
        e.apellido,
        e.fecha_nacimiento,
        e.telefono_celular,
        e.email,
        e.curso_id,
        c.anio,
        c.division,
        c.grado,
        esp.nombre as especialidad,
        t.nombre as turno
    FROM estudiantes e
    LEFT JOIN cursos c ON e.curso_id = c.id
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    LEFT JOIN turnos t ON c.turno_id = t.id
    WHERE e.activo = 1
    ORDER BY e.apellido, e.nombre
    """
    
    GET_STUDENTS_BY_COURSE = """
    SELECT 
        e.id,
        e.nombre,
        e.apellido,
        e.dni,
        e.email,
        c.anio,
        c.division
    FROM estudiantes e
    JOIN cursos c ON e.curso_id = c.id
    WHERE e.activo = 1 AND c.id = %s
    ORDER BY e.apellido, e.nombre
    """
    
    GET_STUDENT_BY_ID = """
    SELECT 
        e.*,
        c.anio,
        c.division,
        c.grado,
        esp.nombre as especialidad,
        t.nombre as turno
    FROM estudiantes e
    LEFT JOIN cursos c ON e.curso_id = c.id
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    LEFT JOIN turnos t ON c.turno_id = t.id
    WHERE e.id = %s AND e.activo = 1
    """
    
    # Consultas de notas
    GET_STUDENT_GRADES = """
    SELECT 
        n.id,
        n.nota,
        n.cuatrimestre,
        n.fecha_registro,
        n.observaciones,
        m.nombre as materia,
        m.codigo as materia_codigo
    FROM notas n
    JOIN materias m ON n.materia_id = m.id
    WHERE n.estudiante_id = %s
    ORDER BY n.cuatrimestre DESC, n.fecha_registro DESC
    """
    
    GET_GRADES_BY_COURSE = """
    SELECT 
        e.nombre,
        e.apellido,
        m.nombre as materia,
        n.nota,
        n.cuatrimestre,
        n.fecha_registro
    FROM notas n
    JOIN estudiantes e ON n.estudiante_id = e.id
    JOIN materias m ON n.materia_id = m.id
    JOIN cursos c ON e.curso_id = c.id
    WHERE c.id = %s
    ORDER BY e.apellido, m.nombre, n.cuatrimestre
    """
    
    GET_AVERAGE_GRADES = """
    SELECT 
        m.nombre as materia,
        AVG(n.nota) as promedio,
        COUNT(n.nota) as total_notas,
        c.anio,
        c.division
    FROM notas n
    JOIN materias m ON n.materia_id = m.id
    JOIN estudiantes e ON n.estudiante_id = e.id
    JOIN cursos c ON e.curso_id = c.id
    WHERE c.id = %s
    GROUP BY m.id, m.nombre, c.anio, c.division
    ORDER BY promedio DESC
    """
    
    # Consultas de profesores
    GET_ALL_TEACHERS = """
    SELECT 
        p.id,
        p.dni,
        p.nombre,
        p.apellido,
        p.email,
        p.telefono_celular,
        p.titulo,
        p.fecha_ingreso,
        p.activo
    FROM profesores p
    WHERE p.activo = 1
    ORDER BY p.apellido, p.nombre
    """
    
    GET_TEACHER_BY_ID = """
    SELECT 
        p.*,
        GROUP_CONCAT(DISTINCT m.nombre) as materias
    FROM profesores p
    LEFT JOIN profesor_materia pm ON p.id = pm.profesor_id
    LEFT JOIN materias m ON pm.materia_id = m.id
    WHERE p.id = %s AND p.activo = 1
    GROUP BY p.id
    """
    
    # Consultas de cursos
    GET_ALL_COURSES = """
    SELECT 
        c.id,
        c.anio,
        c.division,
        c.grado,
        esp.nombre as especialidad,
        t.nombre as turno,
        t.hora_inicio,
        t.hora_fin,
        COUNT(e.id) as total_estudiantes
    FROM cursos c
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    LEFT JOIN turnos t ON c.turno_id = t.id
    LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
    WHERE c.activo = 1
    GROUP BY c.id
    ORDER BY c.anio, c.division
    """
    
    # Consultas de llamados de atención
    GET_ATTENTION_CALLS = """
    SELECT 
        la.id,
        la.fecha,
        la.motivo,
        la.sancion,
        la.observaciones,
        e.nombre,
        e.apellido,
        c.anio,
        c.division
    FROM llamados_atencion la
    JOIN estudiantes e ON la.estudiante_id = e.id
    JOIN cursos c ON e.curso_id = c.id
    ORDER BY la.fecha DESC
    LIMIT 50
    """
    
    GET_ATTENTION_CALLS_BY_STUDENT = """
    SELECT 
        la.*,
        c.anio,
        c.division
    FROM llamados_atencion la
    JOIN estudiantes e ON la.estudiante_id = e.id
    JOIN cursos c ON e.curso_id = c.id
    WHERE la.estudiante_id = %s
    ORDER BY la.fecha DESC
    """
    
    # Consultas de materias
    GET_ALL_SUBJECTS = """
    SELECT 
        m.id,
        m.nombre,
        m.codigo,
        m.horas_semanales,
        esp.nombre as especialidad,
        m.activo
    FROM materias m
    LEFT JOIN especialidades esp ON m.especialidad_id = esp.id
    WHERE m.activo = 1
    ORDER BY m.nombre
    """
    
    # Consultas de especialidades
    GET_ALL_SPECIALTIES = """
    SELECT 
        id,
        nombre,
        descripcion,
        activa
    FROM especialidades
    WHERE activa = 1
    ORDER BY nombre
    """
    
    # Consultas de horarios
    GET_SCHEDULES = """
    SELECT 
        h.id,
        c.anio,
        c.division,
        m.nombre as materia,
        p.nombre as profesor_nombre,
        p.apellido as profesor_apellido,
        h.dia,
        h.hora_inicio,
        h.hora_fin,
        h.aula
    FROM horarios h
    JOIN cursos c ON h.curso_id = c.id
    JOIN materias m ON h.materia_id = m.id
    JOIN profesores p ON h.profesor_id = p.id
    WHERE c.activo = 1
    ORDER BY h.dia, h.hora_inicio
    """
    
    # Consultas de estadísticas
    GET_STATISTICS = """
    SELECT 
        'estudiantes' as tabla,
        COUNT(*) as total
    FROM estudiantes 
    WHERE activo = 1
    
    UNION ALL
    
    SELECT 
        'profesores' as tabla,
        COUNT(*) as total
    FROM profesores 
    WHERE activo = 1
    
    UNION ALL
    
    SELECT 
        'cursos' as tabla,
        COUNT(*) as total
    FROM cursos 
    WHERE activo = 1
    
    UNION ALL
    
    SELECT 
        'materias' as tabla,
        COUNT(*) as total
    FROM materias 
    WHERE activo = 1
    
    UNION ALL
    
    SELECT 
        'notas' as tabla,
        COUNT(*) as total
    FROM notas
    
    UNION ALL
    
    SELECT 
        'llamados' as tabla,
        COUNT(*) as total
    FROM llamados_atencion
    """
    
    GET_COURSE_STATISTICS = """
    SELECT 
        c.anio,
        c.division,
        esp.nombre as especialidad,
        COUNT(e.id) as total_estudiantes,
        AVG(n.nota) as promedio_notas,
        COUNT(la.id) as total_llamados
    FROM cursos c
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    LEFT JOIN estudiantes e ON c.id = e.curso_id AND e.activo = 1
    LEFT JOIN notas n ON e.id = n.estudiante_id
    LEFT JOIN llamados_atencion la ON e.id = la.estudiante_id
    WHERE c.activo = 1
    GROUP BY c.id, c.anio, c.division, esp.nombre
    ORDER BY c.anio, c.division
    """
    
    # Consultas de búsqueda
    SEARCH_STUDENTS = """
    SELECT 
        e.id,
        e.nombre,
        e.apellido,
        e.dni,
        c.anio,
        c.division
    FROM estudiantes e
    JOIN cursos c ON e.curso_id = c.id
    WHERE e.activo = 1 
    AND (e.nombre LIKE %s OR e.apellido LIKE %s OR e.dni LIKE %s)
    ORDER BY e.apellido, e.nombre
    LIMIT 20
    """
    
    SEARCH_TEACHERS = """
    SELECT 
        p.id,
        p.nombre,
        p.apellido,
        p.email
    FROM profesores p
    WHERE p.activo = 1 
    AND (p.nombre LIKE %s OR p.apellido LIKE %s OR p.email LIKE %s)
    ORDER BY p.apellido, p.nombre
    LIMIT 20
    """
    
    # Consultas de reportes
    GET_ATTENDANCE_REPORT = """
    SELECT 
        e.nombre,
        e.apellido,
        c.anio,
        c.division,
        COUNT(i.id) as total_inasistencias,
        SUM(CASE WHEN i.justificada = 1 THEN 1 ELSE 0 END) as inasistencias_justificadas
    FROM estudiantes e
    JOIN cursos c ON e.curso_id = c.id
    LEFT JOIN inasistencias i ON e.id = i.estudiante_id
    WHERE e.activo = 1
    GROUP BY e.id
    HAVING total_inasistencias > 0
    ORDER BY total_inasistencias DESC
    """
    
    GET_GRADE_DISTRIBUTION = """
    SELECT 
        CASE 
            WHEN n.nota >= 9 THEN 'Excelente (9-10)'
            WHEN n.nota >= 7 THEN 'Bueno (7-8.9)'
            WHEN n.nota >= 6 THEN 'Regular (6-6.9)'
            ELSE 'Insuficiente (<6)'
        END as rango,
        COUNT(*) as cantidad,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM notas), 2) as porcentaje
    FROM notas n
    GROUP BY rango
    ORDER BY MIN(n.nota) DESC
    """
