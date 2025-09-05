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

// Incluir header para tener acceso a hasRole()
include 'includes/header.php';

// Verificar permisos (solo admin y directivo)
if (!(hasRole('admin') || hasRole('directivo'))) {
    header('Location: index.php?error=unauthorized');
    exit();
}

$estudiante_id = $_GET['id'] ?? 0;

if (!$estudiante_id) {
    header('Location: estudiantes.php');
    exit();
}

$db = Database::getInstance();

// Obtener datos del estudiante
$estudiante = $db->fetch("
    SELECT e.*, c.anio, c.division, esp.nombre as especialidad
    FROM estudiantes e
    LEFT JOIN cursos c ON e.curso_id = c.id
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    WHERE e.id = ? AND e.activo = 1
", [$estudiante_id]);

if (!$estudiante) {
    header('Location: estudiantes.php');
    exit();
}

// Obtener notas del estudiante
$notas_estudiante = [];
if ($estudiante['curso_id']) {
    $materias = $db->fetchAll("
        SELECT m.* FROM materias m
        INNER JOIN materia_curso mc ON m.id = mc.materia_id
        WHERE mc.curso_id = ? AND m.activa = 1
        ORDER BY m.nombre
    ", [$estudiante['curso_id']]);
    
    $notas_raw = $db->fetchAll("
        SELECT n.*, m.nombre as materia_nombre
        FROM notas n
        LEFT JOIN materias m ON n.materia_id = m.id
        WHERE n.estudiante_id = ? AND n.cuatrimestre IN (1, 2, 3)
        ORDER BY n.cuatrimestre, m.nombre
    ", [$estudiante_id]);
    
    // Organizar notas
    $notas_organizadas = [];
    foreach ($materias as $materia) {
        $notas_organizadas[$materia['id']] = [
            'materia' => $materia,
            'cuatrimestres' => [1 => null, 2 => null, 3 => null],
            'promedio' => null
        ];
    }
    
    foreach ($notas_raw as $nota) {
        if (isset($notas_organizadas[$nota['materia_id']])) {
            $notas_organizadas[$nota['materia_id']]['cuatrimestres'][$nota['cuatrimestre']] = $nota['nota'];
        }
    }
    
    foreach ($notas_organizadas as $materia_id => &$datos) {
        $notas_validas = array_filter($datos['cuatrimestres'], function($nota) {
            return $nota !== null;
        });
        if (count($notas_validas) === 3) {
            $datos['promedio'] = round(array_sum($notas_validas) / count($notas_validas), 2);
            $datos['promedio_calculado'] = true;
        } else {
            $datos['promedio'] = null;
            $datos['promedio_calculado'] = false;
        }
    }
    
    $notas_estudiante = $notas_organizadas;
}

$fecha_actual = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boletín de Notas - <?php echo htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']); ?></title>
    <style>
        @media print {
            @page {
                margin: 1cm;
                size: A4 landscape;
            }
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            background: white;
            color: #000;
            line-height: 1.4;
        }
        
        .boletin-container {
            max-width: 29.7cm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            text-align: center;
            padding: 20px 20px 15px;
            border-bottom: 3px solid #1a4b84;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .school-logo {
            width: 60px;
            height: 60px;
            background: #1a4b84;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        
        .school-info h1 {
            color: #1a4b84;
            margin: 0 0 3px 0;
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .school-info h2 {
            color: #495057;
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: normal;
        }
        
        .boletin-title {
            background: #1a4b84;
            color: white;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0;
        }
        
        .student-info-section {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .student-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-group {
            display: flex;
            align-items: center;
        }
        
        .info-label {
            font-weight: bold;
            color: #1a4b84;
            min-width: 80px;
            font-size: 12px;
        }
        
        .info-value {
            font-size: 12px;
            color: #495057;
            border-bottom: 1px solid #ced4da;
            padding: 3px 0;
            flex: 1;
        }
        
        .academic-year {
            text-align: center;
            background: #1a4b84;
            color: white;
            padding: 8px;
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }
        
        .grades-section {
            padding: 15px;
        }
        
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 11px;
        }
        
        .grades-table th {
            background: #1a4b84;
            color: white;
            padding: 8px 6px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #1a4b84;
            height: 90px;
        }
        
        .grades-table td {
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #dee2e6;
            vertical-align: middle;
            width: 50px;
        }
        
        .grades-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .promedio-row {
            background: #f8f9fa !important;
            border-top: 2px solid #dee2e6;
        }
        
        .estado-row {
            background: #f8f9fa !important;
            border-top: 1px solid #dee2e6;
        }
        
        .cuatrimestre-cell {
            text-align: left;
            font-weight: bold;
            color: #1a4b84;
            padding-left: 15px !important;
            background: #f8f9fa;
            width: 100px;
        }
        
        .materia-header {
            text-align: center;
            font-weight: bold;
            color: #1a4b84;
            background: #e9ecef;
            height: 90px;
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        
        .materia-header strong {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(90deg);
            white-space: normal;
            font-size: 9px;
            max-width: 60px;
            text-align: center;
            line-height: 1.2;
            word-wrap: break-word;
        }
        
        .nota-value {
            font-weight: bold;
            font-size: 12px;
            color: #495057;
        }
        
        .promedio-value {
            font-weight: bold;
            font-size: 12px;
            color: #495057;
        }
        
        .promedio-pendiente {
            color: #6c757d;
            font-style: italic;
            font-size: 11px;
        }
        
        .estado.aprobado {
            color: #495057;
            font-weight: bold;
            font-size: 11px;
        }
        
        .estado.reprobado {
            color: #495057;
            font-weight: bold;
            font-size: 11px;
        }
        
        .estado.pendiente {
            color: #6c757d;
            font-style: italic;
            font-size: 11px;
        }
        
        .summary-section {
            padding: 15px;
            background: #f8f9fa;
            border-top: 2px solid #dee2e6;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .summary-item {
            text-align: center;
            padding: 10px;
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 6px;
        }
        
        .summary-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #1a4b84;
        }
        
        .footer {
            padding: 20px;
            text-align: center;
            background: #343a40;
            color: white;
            font-size: 12px;
        }
        
        .signature-section {
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 20px;
            border-top: 2px solid #dee2e6;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 2px solid #000;
            margin: 30px 0 8px;
            height: 2px;
        }
        
        .signature-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(26, 75, 132, 0.1);
            z-index: -1;
            pointer-events: none;
        }
        
        @media print {
            .boletin-container {
                box-shadow: none;
            }
            
            .watermark {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="watermark">EEST N°2</div>
    
    <div class="boletin-container">
        <div class="header">
            <div class="school-logo">EEST</div>
            <div class="school-info">
                <h1>E.E.S.T. N°2 "Educación y Trabajo"</h1>
                <h2>Escuela de Educación Secundaria Técnica</h2>
            </div>
            <div class="boletin-title">Boletín de Calificaciones</div>
        </div>
        
        <div class="student-info-section">
            <div class="student-info-grid">
                <div class="info-group">
                    <div class="info-label">Estudiante:</div>
                    <div class="info-value"><?php echo htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">DNI:</div>
                    <div class="info-value"><?php echo htmlspecialchars($estudiante['dni']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Curso:</div>
                    <div class="info-value"><?php echo $estudiante['anio'] . '° ' . $estudiante['division'] . ' - ' . $estudiante['especialidad']; ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Fecha:</div>
                    <div class="info-value"><?php echo $fecha_actual; ?></div>
                </div>
            </div>
        </div>
        
        <div class="academic-year">
            Año Lectivo <?php echo date('Y'); ?>
        </div>
        
        <div class="grades-section">
            <?php if (!empty($notas_estudiante)): ?>
            <table class="grades-table">
                <thead>
                    <tr>
                        <th class="cuatrimestre-cell">Cuatrimestre</th>
                        <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                        <th class="materia-header">
                            <strong><?php echo htmlspecialchars($datos['materia']['nombre']); ?></strong>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $materias_aprobadas = 0;
                    $materias_reprobadas = 0;
                    $materias_pendientes = 0;
                    $total_promedio = 0;
                    $materias_con_promedio = 0;
                    
                    // Calcular estadísticas primero
                    foreach ($notas_estudiante as $materia_id => $datos): 
                        if ($datos['promedio_calculado']) {
                            $materias_con_promedio++;
                            $total_promedio += $datos['promedio'];
                            if ($datos['promedio'] >= 7) {
                                $materias_aprobadas++;
                            } else {
                                $materias_reprobadas++;
                            }
                        } else {
                            $materias_pendientes++;
                        }
                    endforeach;
                    ?>
                    
                    <tr>
                        <td class="cuatrimestre-cell">
                            <strong>1° Cuatrimestre</strong>
                        </td>
                        <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                        <td>
                            <span class="nota-value"><?php echo $datos['cuatrimestres'][1] ?? '-'; ?></span>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <tr>
                        <td class="cuatrimestre-cell">
                            <strong>2° Cuatrimestre</strong>
                        </td>
                        <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                        <td>
                            <span class="nota-value"><?php echo $datos['cuatrimestres'][2] ?? '-'; ?></span>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <tr>
                        <td class="cuatrimestre-cell">
                            <strong>3° Cuatrimestre</strong>
                        </td>
                        <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                        <td>
                            <span class="nota-value"><?php echo $datos['cuatrimestres'][3] ?? '-'; ?></span>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <tr class="promedio-row">
                        <td class="cuatrimestre-cell">
                            <strong>Promedio</strong>
                        </td>
                        <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                        <td>
                            <?php if ($datos['promedio_calculado']): ?>
                                <span class="promedio-value"><?php echo $datos['promedio']; ?></span>
                            <?php else: ?>
                                <span class="promedio-pendiente">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <tr class="estado-row">
                        <td class="cuatrimestre-cell">
                            <strong>Estado</strong>
                        </td>
                        <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                        <td>
                            <?php if ($datos['promedio_calculado']): ?>
                                <?php if ($datos['promedio'] >= 7): ?>
                                    <span class="estado aprobado">Aprobado</span>
                                <?php else: ?>
                                    <span class="estado reprobado">Reprobado</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="estado pendiente">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align: center; padding: 2rem; color: #6c757d;">
                <p>No hay notas registradas para este estudiante</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($notas_estudiante)): ?>
        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Materias Aprobadas</div>
                    <div class="summary-value"><?php echo $materias_aprobadas; ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Materias Reprobadas</div>
                    <div class="summary-value"><?php echo $materias_reprobadas; ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Materias Pendientes</div>
                    <div class="summary-value"><?php echo $materias_pendientes; ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Materias</div>
                    <div class="summary-value"><?php echo count($notas_estudiante); ?></div>
                </div>
            </div>
            <?php if ($materias_con_promedio > 0): ?>
            <div style="text-align: center; margin-top: 15px;">
                <div style="font-size: 12px; color: #6c757d; margin-bottom: 3px;">PROMEDIO GENERAL</div>
                <div style="font-size: 28px; font-weight: bold; color: #1a4b84;"><?php echo round($total_promedio / $materias_con_promedio, 2); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Preceptor</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Director</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Sello Institucional</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Fecha</div>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>E.E.S.T. N°2 "Educación y Trabajo"</strong> - Av. Principal 123, Ciudad - Tel: (123) 456-7890</p>
            <p>Este documento fue generado automáticamente por el Sistema Administrativo - <?php echo $fecha_actual; ?></p>
        </div>
    </div>
    
    <script>
        // Imprimir automáticamente cuando se carga la página
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
