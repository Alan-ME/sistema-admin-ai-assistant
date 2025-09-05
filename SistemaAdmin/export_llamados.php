<?php
// Iniciar sesión al principio
session_start();

// Incluir la nueva arquitectura
require_once 'src/autoload.php';
require_once 'config/database.php';

use SistemaAdmin\Services\ServicioAutenticacion;

// Verificar autenticación con la nueva arquitectura
$db = Database::getInstance();
$servicioAutenticacion = new ServicioAutenticacion($db);

// Verificar si hay sesión activa
$usuario = $servicioAutenticacion->verificarSesion();
if (!$usuario) {
    header('Location: login.php');
    exit();
}

// Obtener filtros de la URL
$curso_filter = $_GET['curso'] ?? '';
$estudiante_filter = $_GET['estudiante'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$motivo_filter = $_GET['motivo'] ?? '';
$sancion_filter = $_GET['sancion'] ?? '';

// Construir consulta con filtros
$where_conditions = ["1=1"];
$params = [];

if ($fecha_desde) {
    $where_conditions[] = "l.fecha >= ?";
    $params[] = $fecha_desde;
}

if ($fecha_hasta) {
    $where_conditions[] = "l.fecha <= ?";
    $params[] = $fecha_hasta;
}

if ($curso_filter) {
    $where_conditions[] = "e.curso_id = ?";
    $params[] = $curso_filter;
}

if ($estudiante_filter) {
    $where_conditions[] = "e.id = ?";
    $params[] = $estudiante_filter;
}

if ($motivo_filter) {
    $where_conditions[] = "l.motivo LIKE ?";
    $params[] = "%$motivo_filter%";
}

if ($sancion_filter) {
    $where_conditions[] = "l.sancion LIKE ?";
    $params[] = "%$sancion_filter%";
}

$where_clause = implode(" AND ", $where_conditions);

// Obtener datos para exportar
$llamados = $db->fetchAll("
    SELECT l.fecha, e.dni, e.apellido, e.nombre,
           c.anio, c.division, esp.nombre as especialidad, t.nombre as turno,
           l.motivo, l.sancion, l.observaciones,
           u.apellido as usuario_apellido, u.nombre as usuario_nombre,
           l.fecha_registro
    FROM llamados_atencion l
    JOIN estudiantes e ON l.estudiante_id = e.id
    LEFT JOIN cursos c ON e.curso_id = c.id
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    LEFT JOIN turnos t ON c.turno_id = t.id
    LEFT JOIN usuarios u ON l.usuario_id = u.id
    WHERE $where_clause
    ORDER BY l.fecha DESC, e.apellido, e.nombre
", $params);

// Configurar headers para descarga de Excel
$filename = 'llamados_atencion_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Crear archivo CSV
$output = fopen('php://output', 'w');

// BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados
$headers = [
    'Fecha',
    'DNI',
    'Apellido',
    'Nombre',
    'Año',
    'División',
    'Especialidad',
    'Turno',
    'Motivo',
    'Sanción',
    'Observaciones',
    'Registrado por',
    'Fecha de Registro'
];

fputcsv($output, $headers, ';');

// Datos
foreach ($llamados as $llamado) {
    $row = [
        date('d/m/Y', strtotime($llamado['fecha'])),
        $llamado['dni'],
        $llamado['apellido'],
        $llamado['nombre'],
        $llamado['anio'] ?: '',
        $llamado['division'] ?: '',
        $llamado['especialidad'] ?: '',
        $llamado['turno'] ?: '',
        $llamado['motivo'],
        $llamado['sancion'] ?: 'Sin sanción',
        $llamado['observaciones'] ?: '',
        ($llamado['usuario_apellido'] && $llamado['usuario_nombre']) ? 
            $llamado['usuario_apellido'] . ', ' . $llamado['usuario_nombre'] : '',
        $llamado['fecha_registro'] ? date('d/m/Y H:i', strtotime($llamado['fecha_registro'])) : ''
    ];
    
    fputcsv($output, $row, ';');
}

// Agregar estadísticas al final
fputcsv($output, [], ';'); // Línea vacía
fputcsv($output, ['ESTADÍSTICAS'], ';');
fputcsv($output, [], ';'); // Línea vacía

$total_llamados = count($llamados);
$con_sancion = count(array_filter($llamados, function($l) { return !empty($l['sancion']) && $l['sancion'] !== 'Sin sanción'; }));
$sin_sancion = $total_llamados - $con_sancion;

fputcsv($output, ['Total de llamados:', $total_llamados], ';');
fputcsv($output, ['Con sanción:', $con_sancion], ';');
fputcsv($output, ['Sin sanción:', $sin_sancion], ';');

// Estadísticas por motivo
$motivos_count = [];
foreach ($llamados as $llamado) {
    $motivo = $llamado['motivo'];
    $motivos_count[$motivo] = ($motivos_count[$motivo] ?? 0) + 1;
}

if (!empty($motivos_count)) {
    fputcsv($output, [], ';'); // Línea vacía
    fputcsv($output, ['DISTRIBUCIÓN POR MOTIVO'], ';');
    arsort($motivos_count); // Ordenar por cantidad descendente
    foreach (array_slice($motivos_count, 0, 10, true) as $motivo => $cantidad) {
        fputcsv($output, [$motivo . ':', $cantidad], ';');
    }
}

// Estadísticas por sanción
$sanciones_count = [];
foreach ($llamados as $llamado) {
    if (!empty($llamado['sancion'])) {
        $sancion = $llamado['sancion'];
        $sanciones_count[$sancion] = ($sanciones_count[$sancion] ?? 0) + 1;
    }
}

if (!empty($sanciones_count)) {
    fputcsv($output, [], ';'); // Línea vacía
    fputcsv($output, ['DISTRIBUCIÓN POR SANCIÓN'], ';');
    arsort($sanciones_count); // Ordenar por cantidad descendente
    foreach ($sanciones_count as $sancion => $cantidad) {
        fputcsv($output, [$sancion . ':', $cantidad], ';');
    }
}

// Filtros aplicados
fputcsv($output, [], ';'); // Línea vacía
fputcsv($output, ['FILTROS APLICADOS'], ';');
fputcsv($output, ['Fecha desde:', $fecha_desde ?: 'Sin filtro'], ';');
fputcsv($output, ['Fecha hasta:', $fecha_hasta ?: 'Sin filtro'], ';');

if ($curso_filter) {
    $curso_info = $db->fetch("
        SELECT c.anio, c.division, esp.nombre as especialidad 
        FROM cursos c 
        LEFT JOIN especialidades esp ON c.especialidad_id = esp.id 
        WHERE c.id = ?", [$curso_filter]);
    if ($curso_info) {
        fputcsv($output, ['Curso:', $curso_info['anio'] . '° ' . $curso_info['division'] . ' - ' . $curso_info['especialidad']], ';');
    }
}

if ($estudiante_filter) {
    $estudiante_info = $db->fetch("SELECT apellido, nombre FROM estudiantes WHERE id = ?", [$estudiante_filter]);
    if ($estudiante_info) {
        fputcsv($output, ['Estudiante:', $estudiante_info['apellido'] . ', ' . $estudiante_info['nombre']], ';');
    }
}

if ($motivo_filter) {
    fputcsv($output, ['Motivo filtrado:', $motivo_filter], ';');
}

if ($sancion_filter) {
    fputcsv($output, ['Sanción filtrada:', $sancion_filter], ';');
}

fputcsv($output, [], ';'); // Línea vacía
fputcsv($output, ['Exportado el:', date('d/m/Y H:i:s')], ';');
fputcsv($output, ['Exportado por:', $_SESSION['apellido'] . ', ' . $_SESSION['nombre']], ';');

fclose($output);
exit();
?>
