<?php
/**
 * API REST para SistemaAdmin AI Assistant
 * Compatible con plan gratuito de Render
 */

// Configuración de headers para CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir archivos necesarios
require_once 'src/autoload.php';
require_once 'includes/character_encoding.php';

// Configuración de la API
$API_KEY = 'sistema_admin_ai_2024_secure_key';
$RATE_LIMIT = 100; // consultas por hora por IP
$CACHE_TIME = 300; // 5 minutos

// Inicializar sesión para manejo de errores
session_start();

try {
    // Verificar autenticación
    $api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
    if ($api_key !== $API_KEY) {
        throw new Exception('API key inválida', 401);
    }
    
    // Rate limiting simple
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_limit_key = "rate_limit_{$client_ip}";
    
    if (!isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key] = ['count' => 0, 'reset_time' => time() + 3600];
    }
    
    $rate_data = $_SESSION[$rate_limit_key];
    if (time() > $rate_data['reset_time']) {
        $_SESSION[$rate_limit_key] = ['count' => 0, 'reset_time' => time() + 3600];
        $rate_data = $_SESSION[$rate_limit_key];
    }
    
    if ($rate_data['count'] >= $RATE_LIMIT) {
        throw new Exception('Rate limit excedido. Máximo 100 consultas por hora.', 429);
    }
    
    $_SESSION[$rate_limit_key]['count']++;
    
    // Obtener acción
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Cache simple
    $cache_key = "api_cache_{$action}_" . md5(serialize($_GET) . serialize($_POST));
    $cache_file = "logs/cache_{$cache_key}.json";
    
    // Verificar cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $CACHE_TIME) {
        $cached_data = json_decode(file_get_contents($cache_file), true);
        if ($cached_data) {
            echo json_encode($cached_data);
            exit;
        }
    }
    
    // Procesar acción
    $response = processAction($action);
    
    // Guardar en cache
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }
    file_put_contents($cache_file, json_encode($response));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode() ?: 500,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    http_response_code($e->getCode() ?: 500);
    echo json_encode($error_response);
}

/**
 * Procesar acciones de la API
 */
function processAction($action) {
    global $database;
    
    switch ($action) {
        case 'health':
            return getHealthCheck();
            
        case 'estadisticas':
            return getEstadisticas();
            
        case 'estudiantes':
            return getEstudiantes();
            
        case 'estudiante':
            $id = $_GET['id'] ?? '';
            return getEstudiante($id);
            
        case 'estudiantes_curso':
            $curso_id = $_GET['curso_id'] ?? '';
            return getEstudiantesCurso($curso_id);
            
        case 'buscar_estudiantes':
            $search = $_GET['search'] ?? '';
            return buscarEstudiantes($search);
            
        case 'notas_estudiante':
            $estudiante_id = $_GET['estudiante_id'] ?? '';
            return getNotasEstudiante($estudiante_id);
            
        case 'profesores':
            return getProfesores();
            
        case 'cursos':
            return getCursos();
            
        case 'materias':
            return getMaterias();
            
        case 'especialidades':
            return getEspecialidades();
            
        case 'llamados':
            return getLlamados();
            
        case 'horarios':
            return getHorarios();
            
        case 'reporte_asistencia':
            return getReporteAsistencia();
            
        case 'estadisticas_curso':
            $curso_id = $_GET['curso_id'] ?? '';
            return getEstadisticasCurso($curso_id);
            
        default:
            throw new Exception('Acción no encontrada', 404);
    }
}

/**
 * Health check
 */
function getHealthCheck() {
    try {
        $database = getDatabaseConnection();
        $result = $database->query("SELECT COUNT(*) as total FROM estudiantes WHERE activo = 1");
        $total_estudiantes = $result->fetch_assoc()['total'];
        
        return [
            'success' => true,
            'status' => 'healthy',
            'database' => 'connected',
            'total_estudiantes' => (int)$total_estudiantes,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * Obtener estadísticas generales
 */
function getEstadisticas() {
    $database = getDatabaseConnection();
    
    $stats = [];
    
    // Estudiantes
    $result = $database->query("SELECT COUNT(*) as total FROM estudiantes WHERE activo = 1");
    $stats['estudiantes'] = (int)$result->fetch_assoc()['total'];
    
    // Profesores
    $result = $database->query("SELECT COUNT(*) as total FROM profesores WHERE activo = 1");
    $stats['profesores'] = (int)$result->fetch_assoc()['total'];
    
    // Cursos
    $result = $database->query("SELECT COUNT(*) as total FROM cursos WHERE activo = 1");
    $stats['cursos'] = (int)$result->fetch_assoc()['total'];
    
    // Materias
    $result = $database->query("SELECT COUNT(*) as total FROM materias WHERE activo = 1");
    $stats['materias'] = (int)$result->fetch_assoc()['total'];
    
    // Notas
    $result = $database->query("SELECT COUNT(*) as total FROM notas");
    $stats['notas'] = (int)$result->fetch_assoc()['total'];
    
    // Llamados
    $result = $database->query("SELECT COUNT(*) as total FROM llamados_atencion");
    $stats['llamados'] = (int)$result->fetch_assoc()['total'];
    
    return [
        'success' => true,
        'data' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener lista de estudiantes
 */
function getEstudiantes() {
    $database = getDatabaseConnection();
    
    $query = "
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
        LIMIT 100
    ";
    
    $result = $database->query($query);
    $estudiantes = [];
    
    while ($row = $result->fetch_assoc()) {
        $estudiantes[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $estudiantes,
        'count' => count($estudiantes),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener estudiante específico
 */
function getEstudiante($id) {
    if (empty($id)) {
        throw new Exception('ID de estudiante requerido', 400);
    }
    
    $database = getDatabaseConnection();
    
    $query = "
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
        WHERE e.id = ? AND e.activo = 1
    ";
    
    $stmt = $database->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Estudiante no encontrado', 404);
    }
    
    $estudiante = $result->fetch_assoc();
    
    // Obtener notas del estudiante
    $notas_query = "
        SELECT 
            n.id,
            n.nota,
            n.cuatrimestre,
            n.fecha_registro,
            n.observaciones,
            m.nombre as materia
        FROM notas n
        JOIN materias m ON n.materia_id = m.id
        WHERE n.estudiante_id = ?
        ORDER BY n.cuatrimestre DESC, n.fecha_registro DESC
        LIMIT 20
    ";
    
    $stmt = $database->prepare($notas_query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $notas_result = $stmt->get_result();
    
    $notas = [];
    while ($row = $notas_result->fetch_assoc()) {
        $notas[] = $row;
    }
    
    $estudiante['notas'] = $notas;
    
    return [
        'success' => true,
        'data' => $estudiante,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener estudiantes por curso
 */
function getEstudiantesCurso($curso_id) {
    if (empty($curso_id)) {
        throw new Exception('ID de curso requerido', 400);
    }
    
    $database = getDatabaseConnection();
    
    $query = "
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
        WHERE e.activo = 1 AND c.id = ?
        ORDER BY e.apellido, e.nombre
    ";
    
    $stmt = $database->prepare($query);
    $stmt->bind_param('i', $curso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $estudiantes = [];
    while ($row = $result->fetch_assoc()) {
        $estudiantes[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $estudiantes,
        'count' => count($estudiantes),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Buscar estudiantes
 */
function buscarEstudiantes($search) {
    if (empty($search)) {
        throw new Exception('Término de búsqueda requerido', 400);
    }
    
    $database = getDatabaseConnection();
    
    $query = "
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
        AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.dni LIKE ?)
        ORDER BY e.apellido, e.nombre
        LIMIT 20
    ";
    
    $search_term = "%{$search}%";
    $stmt = $database->prepare($query);
    $stmt->bind_param('sss', $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $estudiantes = [];
    while ($row = $result->fetch_assoc()) {
        $estudiantes[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $estudiantes,
        'count' => count($estudiantes),
        'search_term' => $search,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener notas de estudiante
 */
function getNotasEstudiante($estudiante_id) {
    if (empty($estudiante_id)) {
        throw new Exception('ID de estudiante requerido', 400);
    }
    
    $database = getDatabaseConnection();
    
    $query = "
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
        WHERE n.estudiante_id = ?
        ORDER BY n.cuatrimestre DESC, n.fecha_registro DESC
    ";
    
    $stmt = $database->prepare($query);
    $stmt->bind_param('i', $estudiante_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notas = [];
    while ($row = $result->fetch_assoc()) {
        $notas[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $notas,
        'count' => count($notas),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener profesores
 */
function getProfesores() {
    $database = getDatabaseConnection();
    
    $query = "
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
    ";
    
    $result = $database->query($query);
    $profesores = [];
    
    while ($row = $result->fetch_assoc()) {
        $profesores[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $profesores,
        'count' => count($profesores),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener cursos
 */
function getCursos() {
    $database = getDatabaseConnection();
    
    $query = "
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
    ";
    
    $result = $database->query($query);
    $cursos = [];
    
    while ($row = $result->fetch_assoc()) {
        $cursos[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $cursos,
        'count' => count($cursos),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener materias
 */
function getMaterias() {
    $database = getDatabaseConnection();
    
    $query = "
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
    ";
    
    $result = $database->query($query);
    $materias = [];
    
    while ($row = $result->fetch_assoc()) {
        $materias[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $materias,
        'count' => count($materias),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener especialidades
 */
function getEspecialidades() {
    $database = getDatabaseConnection();
    
    $query = "
        SELECT 
            id,
            nombre,
            descripcion,
            activa
        FROM especialidades
        WHERE activa = 1
        ORDER BY nombre
    ";
    
    $result = $database->query($query);
    $especialidades = [];
    
    while ($row = $result->fetch_assoc()) {
        $especialidades[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $especialidades,
        'count' => count($especialidades),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener llamados de atención
 */
function getLlamados() {
    $database = getDatabaseConnection();
    
    $query = "
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
    ";
    
    $result = $database->query($query);
    $llamados = [];
    
    while ($row = $result->fetch_assoc()) {
        $llamados[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $llamados,
        'count' => count($llamados),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener horarios
 */
function getHorarios() {
    $database = getDatabaseConnection();
    
    $query = "
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
    ";
    
    $result = $database->query($query);
    $horarios = [];
    
    while ($row = $result->fetch_assoc()) {
        $horarios[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $horarios,
        'count' => count($horarios),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener reporte de asistencia
 */
function getReporteAsistencia() {
    $database = getDatabaseConnection();
    
    $query = "
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
        LIMIT 50
    ";
    
    $result = $database->query($query);
    $reporte = [];
    
    while ($row = $result->fetch_assoc()) {
        $reporte[] = $row;
    }
    
    return [
        'success' => true,
        'data' => $reporte,
        'count' => count($reporte),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener estadísticas de curso
 */
function getEstadisticasCurso($curso_id) {
    if (empty($curso_id)) {
        throw new Exception('ID de curso requerido', 400);
    }
    
    $database = getDatabaseConnection();
    
    $query = "
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
        WHERE c.id = ?
        GROUP BY c.id
    ";
    
    $stmt = $database->prepare($query);
    $stmt->bind_param('i', $curso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Curso no encontrado', 404);
    }
    
    $estadisticas = $result->fetch_assoc();
    
    return [
        'success' => true,
        'data' => $estadisticas,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtener conexión a base de datos
 */
function getDatabaseConnection() {
    static $database = null;
    
    if ($database === null) {
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $dbname = 'sistema_admin_eest2';
        
        $database = new mysqli($host, $username, $password, $dbname);
        
        if ($database->connect_error) {
            throw new Exception('Error de conexión a base de datos: ' . $database->connect_error);
        }
        
        $database->set_charset('utf8mb4');
    }
    
    return $database;
}
?>
