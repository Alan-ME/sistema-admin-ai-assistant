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

$pageTitle = 'Boletín de Notas (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';

include 'includes/header.php';

use SistemaAdmin\Controllers\NotaController;
use SistemaAdmin\Mappers\NotaMapper;
use SistemaAdmin\Services\ServicioNotas;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Services\ServicioEstudiantes;

// Inicializar la nueva arquitectura
$db = Database::getInstance();
$notaMapper = new NotaMapper($db);
$estudianteMapper = new EstudianteMapper($db);
$servicioEstudiantes = new ServicioEstudiantes($estudianteMapper);
$servicioNotas = new ServicioNotas($notaMapper, $estudianteMapper);
$notaController = new NotaController($servicioNotas, $servicioEstudiantes);

$action = $_GET['action'] ?? '';
$curso_filter = $_GET['curso'] ?? '';
$estudiante_filter = $_GET['estudiante'] ?? '';
$cuatrimestre_filter = $_GET['cuatrimestre'] ?? '';
$success_message = '';
$error_message = '';
$materias_previas_generadas = 0;

// Si viene un estudiante específico, auto-detectar su curso y seleccionar cuatrimestre actual
if ($estudiante_filter && !$curso_filter) {
    $estudiante_info = $db->fetch("
        SELECT curso_id, apellido, nombre 
        FROM estudiantes 
        WHERE id = ? AND activo = 1
    ", [$estudiante_filter]);
    
    if ($estudiante_info) {
        $curso_filter = $estudiante_info['curso_id'];
        // Si no hay cuatrimestre seleccionado, usar el cuatrimestre actual basado en la fecha
        if (!$cuatrimestre_filter) {
            $mes_actual = date('n');
            if ($mes_actual >= 3 && $mes_actual <= 6) {
                $cuatrimestre_filter = '1'; // Primer cuatrimestre (marzo-junio)
            } elseif ($mes_actual >= 7 && $mes_actual <= 10) {
                $cuatrimestre_filter = '2'; // Segundo cuatrimestre (julio-octubre)
            } else {
                $cuatrimestre_filter = '3'; // Tercer cuatrimestre (noviembre-febrero)
            }
        }
    }
}

// Solo admin/directivo pueden modificar notas
$can_manage = (hasRole('admin') || hasRole('directivo'));

// Actualizar nota usando la nueva arquitectura
if ($can_manage && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_nota'])) {
    try {
        $nota_id = $_POST['nota_id'];
        $nuevo_valor = $_POST['nota'] !== '' ? (float)$_POST['nota'] : null;
        $observaciones = $_POST['observaciones'] ?: null;
        
        if ($nuevo_valor !== null) {
            $data = [
                'calificacion' => $nuevo_valor,
                'observaciones' => $observaciones
            ];
            $resultado = $notaController->actualizar($nota_id, $data);
            if ($resultado['success']) {
                $success_message = "Nota actualizada correctamente";
            } else {
                $error_message = $resultado['error'] ?? 'Error al actualizar nota';
            }
        } else {
            $error_message = "El valor de la nota es requerido";
        }
    } catch (Exception $e) {
        $error_message = "Error al actualizar nota: " . $e->getMessage();
    }
}

// Insertar nueva nota usando la nueva arquitectura
if ($can_manage && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insertar_nota'])) {
    try {
        $data = [
            'estudiante_id' => (int)$_POST['estudiante_id'],
            'materia_id' => (int)$_POST['materia_id'],
            'bimestre' => (string)$_POST['cuatrimestre'], // El controller espera 'bimestre'
            'calificacion' => $_POST['nota'] !== '' ? (float)$_POST['nota'] : null,
            'observaciones' => $_POST['observaciones'] ?: null
        ];
        
        $resultado = $notaController->cargar($data);
        
        if ($resultado['success']) {
            $success_message = "Nota registrada correctamente";
        } else {
            $error_message = $resultado['error'] ?? 'Error al registrar nota';
        }
    } catch (Exception $e) {
        $error_message = "Error al registrar nota: " . $e->getMessage();
    }
}

// Borrado de nota usando la nueva arquitectura
if ($can_manage && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_nota'])) {
    try {
        $nota_id = $_POST['nota_id'];
        
        $resultado = $notaController->eliminar($nota_id);
        
        if ($resultado['success']) {
            $success_message = "Nota eliminada";
        } else {
            $error_message = $resultado['error'] ?? 'Error al eliminar nota';
        }
    } catch (Exception $e) {
        $error_message = "Error al eliminar nota: " . $e->getMessage();
    }
}

// Datos para formularios y filtros (usando la base de datos directamente por ahora)
$cursos = $db->fetchAll("
    SELECT c.id, c.anio, c.division, esp.nombre as especialidad
    FROM cursos c
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    WHERE c.activo = 1
    ORDER BY c.anio, c.division
");

// Obtener estudiantes usando la nueva arquitectura
$estudiantes_resultado = $servicioEstudiantes->obtenerTodos();
$estudiantes = [];

// Convertir a array y agregar información del curso
foreach ($estudiantes_resultado as $estudiante) {
    $estudiante_array = $estudiante->toArray();
    
    // Obtener información del curso si existe
    if ($estudiante_array['curso_id']) {
        $curso_info = $db->fetch("
            SELECT c.anio, c.division, esp.nombre as especialidad 
            FROM cursos c 
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id 
            WHERE c.id = ?
        ", [$estudiante_array['curso_id']]);
        
        if ($curso_info) {
            $estudiante_array['anio'] = $curso_info['anio'];
            $estudiante_array['division'] = $curso_info['division'];
            $estudiante_array['especialidad'] = $curso_info['especialidad'];
        }
    }
    
    $estudiantes[] = $estudiante_array;
}

// Obtener materias según el curso seleccionado
$materias = [];
if ($curso_filter) {
    // Obtener solo las materias que están asignadas al curso específico
    $materias = $db->fetchAll("
        SELECT m.* FROM materias m
        INNER JOIN materia_curso mc ON m.id = mc.materia_id
        WHERE mc.curso_id = ? AND m.activa = 1
        ORDER BY m.nombre
    ", [$curso_filter]);
} else {
    // Si no hay curso seleccionado, mostrar todas las materias activas
$materias = $db->fetchAll("SELECT * FROM materias WHERE activa = 1 ORDER BY nombre");
}

// Obtener notas para el boletín usando la nueva arquitectura
$estudiantes_curso = [];
$notas_boletin = [];
if ($curso_filter) {
    // Obtener estudiantes del curso
    $estudiantes_curso = $servicioEstudiantes->obtenerPorCurso($curso_filter);
    
    // Si hay filtro de estudiante específico, filtrar
    if ($estudiante_filter) {
        $estudiantes_curso = array_filter($estudiantes_curso, function($estudiante) use ($estudiante_filter) {
            return $estudiante->getId() == $estudiante_filter;
        });
    }
    
    // Obtener notas para cada estudiante
    foreach ($estudiantes_curso as $estudiante) {
        $notas_estudiante = $servicioNotas->obtenerNotasEstudiante($estudiante->getId());
        
        foreach ($notas_estudiante as $nota) {
            $notas_boletin[] = [
                'id' => $nota->getId(),
                'estudiante_id' => $estudiante->getId(),
                'apellido' => $estudiante->getApellido(),
                'nombre' => $estudiante->getNombre(),
                'materia_id' => $nota->getMateriaId(),
                'materia' => 'Materia ID: ' . $nota->getMateriaId(), // Por ahora, hasta implementar ServicioMaterias
                'cuatrimestre' => $nota->getBimestre(),
                'nota' => $nota->getValor(),
                'observaciones' => $nota->getObservaciones()
            ];
        }
    }
}

// Organizar notas por estudiante, materia y cuatrimestre
$boletin_organizado = [];
if (!empty($estudiantes_curso)) {
    foreach ($estudiantes_curso as $estudiante) {
        $boletin_organizado[$estudiante->getId()] = [
            'estudiante' => [
                'id' => $estudiante->getId(),
                'apellido' => $estudiante->getApellido(),
                'nombre' => $estudiante->getNombre(),
                'dni' => $estudiante->getDni()
            ],
            'notas' => []
        ];
        
        foreach ($materias as $materia) {
            $boletin_organizado[$estudiante->getId()]['notas'][$materia['id']] = [
                'materia' => $materia,
                'cuatrimestres' => [
                    1 => ['nota' => null, 'observaciones' => null, 'nota_id' => null],
                    2 => ['nota' => null, 'observaciones' => null, 'nota_id' => null],
                    3 => ['nota' => null, 'observaciones' => null, 'nota_id' => null]
                ]
            ];
        }
    }
}

// Llenar con las notas existentes de todos los cuatrimestres
foreach ($notas_boletin as $nota) {
    $estudiante_id = $nota['estudiante_id'];
    $materia_id = $nota['materia_id'];
    $cuatrimestre = $nota['cuatrimestre'];
    
    if (isset($boletin_organizado[$estudiante_id]['notas'][$materia_id]['cuatrimestres'][$cuatrimestre])) {
        $boletin_organizado[$estudiante_id]['notas'][$materia_id]['cuatrimestres'][$cuatrimestre] = [
            'nota' => $nota['nota'],
            'observaciones' => $nota['observaciones'],
            'nota_id' => $nota['id']
        ];
    }
}

$total_estudiantes = count($estudiantes_curso);
?>

<section class="notas-section">
    <div class="section-header">
        <h2>Boletín de Notas</h2>
        <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if ($cuatrimestre_filter === 'final'): ?>
        <a href="materias_previas.php" class="btn btn-warning">
            <i class="fas fa-exclamation-triangle"></i> Ver Materias Previas
        </a>
        <?php endif; ?>
        <?php if ($can_manage): ?>
        <a href="notas.php?action=nueva" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Nota Individual
        </a>
        <?php endif; ?>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
    <?php endif; ?>
    


    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <h3><?php echo number_format($total_estudiantes); ?></h3>
                <p>Estudiantes en el Curso</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info"><i class="fas fa-book"></i></div>
            <div class="stat-content">
                <h3><?php echo count($materias); ?></h3>
                <p>Materias</p>
            </div>
        </div>
        <?php if ($cuatrimestre_filter === 'final'): ?>
        <div class="stat-card">
            <div class="stat-icon warning">
                <a href="materias_previas.php" style="color: inherit; text-decoration: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                </a>
            </div>
            <div class="stat-content">
                <h3>
                    <a href="materias_previas.php" style="color: inherit; text-decoration: none;">
                        <?php echo $materias_previas_generadas; ?>
                    </a>
                </h3>
                <p>
                    <a href="materias_previas.php" style="color: inherit; text-decoration: none;">
                        Materias Previas Generadas
                    </a>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Filtros del Boletín
                <?php if ($estudiante_filter && isset($estudiante_info)): ?>
                    <span class="badge badge-info" style="margin-left: 1rem; background: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: var(--border-radius); font-size: 0.75rem;">
                        Estudiante: <?php echo htmlspecialchars($estudiante_info['apellido'] . ', ' . $estudiante_info['nombre']); ?>
                    </span>
                <?php endif; ?>
            </h3>
        </div>
        <form method="GET" class="form-container">
            <?php if ($estudiante_filter): ?>
                <input type="hidden" name="estudiante" value="<?php echo htmlspecialchars($estudiante_filter); ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="curso">Curso *</label>
                    <select name="curso" id="curso" required>
                        <option value="">Seleccionar curso</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>" <?php echo $curso_filter == $curso['id'] ? 'selected' : ''; ?>>
                            <?php echo $curso['anio'] . '° ' . $curso['division'] . ' - ' . $curso['especialidad']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                                 <div class="form-group">
                     <label for="info_cuatrimestre">Información</label>
                     <div style="padding: 0.75rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: var(--border-radius); color: #6c757d;">
                         <i class="fas fa-info-circle"></i> 
                         Se mostrarán automáticamente los 3 cuatrimestres (1°, 2° y 3°) para poder cargar y editar todas las notas.
                     </div>
            </div>
        </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Mostrar Boletín</button>
                <a href="notas.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
            </div>
        </form>
    </div>

    <?php if ($curso_filter && !empty($boletin_organizado)): ?>
    <div class="card">
        <div class="card-header">
                         <h3 class="card-title">
                     📋 Boletín Completo - <?php 
                         $curso_info = $db->fetch("
                             SELECT c.anio, c.division, esp.nombre as especialidad 
                             FROM cursos c 
                             LEFT JOIN especialidades esp ON c.especialidad_id = esp.id 
                             WHERE c.id = ?
                         ", [$curso_filter]);
                         echo $curso_info['anio'] . '° ' . $curso_info['division'] . ' - ' . $curso_info['especialidad'];
                         
                         // Si es un estudiante específico, mostrar su nombre
                         if ($estudiante_filter && isset($estudiante_info)) {
                             echo " - " . $estudiante_info['apellido'] . ", " . $estudiante_info['nombre'];
                         }
                     ?> - Todos los Cuatrimestres
             </h3>
        </div>
        
        <div class="boletin-container">
            <table class="boletin-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="estudiante-col">Estudiante</th>
                                                 <?php foreach ($materias as $materia): ?>
                        <th colspan="3" class="materia-col">
                             <?php echo htmlspecialchars($materia['nombre']); ?>
                         </th>
                         <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($materias as $materia): ?>
                        <th class="cuatrimestre-col">1° Cuatr.</th>
                        <th class="cuatrimestre-col">2° Cuatr.</th>
                        <th class="cuatrimestre-col">3° Cuatr.</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($boletin_organizado as $estudiante_id => $datos): ?>
                    <tr>
                        <td class="estudiante-cell">
                            <strong><?php echo htmlspecialchars($datos['estudiante']['apellido'] . ', ' . $datos['estudiante']['nombre']); ?></strong>
                            <br><small>DNI: <?php echo $datos['estudiante']['dni']; ?></small>
                        </td>
                                                 <?php foreach ($materias as $materia): ?>
                            <?php for ($cuatr = 1; $cuatr <= 3; $cuatr++): ?>
                         <td class="nota-cell">
                             <?php 
                                $nota_data = $datos['notas'][$materia['id']]['cuatrimestres'][$cuatr];
                                if ($can_manage): 
                             ?>
                                 <form method="POST" class="nota-form">
                                     <input type="hidden" name="estudiante_id" value="<?php echo $estudiante_id; ?>">
                                     <input type="hidden" name="materia_id" value="<?php echo $materia['id']; ?>">
                                        <input type="hidden" name="cuatrimestre" value="<?php echo $cuatr; ?>">
                                     <?php if ($nota_data['nota_id']): ?>
                                         <input type="hidden" name="nota_id" value="<?php echo $nota_data['nota_id']; ?>">
                                     <?php endif; ?>
                                     
                                     <div class="nota-input-group">
                                         <input type="number" 
                                                step="0.01" 
                                                min="0" 
                                                max="10" 
                                                name="nota" 
                                                value="<?php echo $nota_data['nota'] ?? ''; ?>" 
                                                placeholder="-" 
                                                class="nota-input"
                                                onchange="this.form.submit()">
                                         <input type="text" 
                                                name="observaciones" 
                                                value="<?php echo htmlspecialchars($nota_data['observaciones'] ?? ''); ?>" 
                                                placeholder="Obs." 
                                                class="obs-input"
                                                title="Observaciones">
    </div>
                                     
                                     <input type="hidden" name="<?php echo $nota_data['nota_id'] ? 'actualizar_nota' : 'insertar_nota'; ?>" value="1">
                                 </form>
                             <?php else: ?>
                                 <div class="nota-display">
                                         <span class="nota-value">
                                             <?php echo $nota_data['nota'] ?? '-'; ?>
                                         </span>
                                         <?php if ($nota_data['observaciones']): ?>
                                             <br><small class="obs-display"><?php echo htmlspecialchars($nota_data['observaciones']); ?></small>
                                     <?php endif; ?>
                                 </div>
                             <?php endif; ?>
                         </td>
                            <?php endfor; ?>
                         <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif ($curso_filter): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-center">No hay estudiantes en este curso o no se encontraron datos.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($can_manage && $action === 'nueva'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Cargar Nueva Nota Individual</h3></div>
        <form method="POST" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="estudiante_id">Estudiante *</label>
                    <select name="estudiante_id" id="estudiante_id" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($estudiantes as $est): ?>
                        <option value="<?php echo $est['id']; ?>">
                            <?php echo htmlspecialchars($est['apellido'] . ', ' . $est['nombre']) . ' - ' . (isset($est['anio']) && $est['anio'] ? $est['anio'] . '° ' . $est['division'] : 'Sin curso'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="materia_id">Materia *</label>
                    <select name="materia_id" id="materia_id" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($materias as $mat): ?>
                        <option value="<?php echo $mat['id']; ?>"><?php echo htmlspecialchars($mat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cuatrimestre">Cuatrimestre *</label>
                    <select name="cuatrimestre" id="cuatrimestre" required>
                        <option value="">Seleccionar</option>
                        <option value="1">1°</option>
                        <option value="2">2°</option>
                        <option value="3">3°</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="nota">Nota (numérica)</label>
                    <input type="number" step="0.01" min="0" max="10" name="nota" id="nota" placeholder="Ej: 7.50">
                </div>
                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <input type="text" name="observaciones" id="observaciones" placeholder="Observaciones (opcional)">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="insertar_nota" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                <a href="notas.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</section>

<style>
/* ===== ESTILOS MODERNOS PARA EL BOLETÍN ===== */

.boletin-container {
    overflow-x: auto;
    margin: 20px 0;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    padding: 3px;
}

.boletin-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.boletin-table th,
.boletin-table td {
    border: none;
    padding: 12px 8px;
    text-align: center;
    vertical-align: middle;
    position: relative;
}

.boletin-table th {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    color: white;
    font-weight: 700;
    font-size: 0.95em;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.estudiante-col {
    min-width: 220px;
    text-align: left !important;
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    color: white;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.materia-col {
    min-width: 130px;
    font-size: 0.9em;
    writing-mode: vertical-rl;
    text-orientation: mixed;
    height: 140px;
    vertical-align: bottom;
    position: relative;
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    color: white;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.materia-promedio-label {
    font-size: 0.7em;
    opacity: 0.9;
    margin-top: 5px;
    font-weight: 400;
}

.estudiante-cell {
    text-align: left !important;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    font-weight: 600;
    color: #495057;
    border-right: 2px solid #dee2e6;
    position: sticky;
    left: 0;
    z-index: 5;
}

.nota-cell {
    padding: 8px 4px !important;
    background: white;
    transition: all 0.3s ease;
    border-bottom: 1px solid #f1f3f4;
}

.nota-cell:hover {
    background: linear-gradient(135deg, #f0f4ff 0%, #dbeafe 100%);
    transform: scale(1.02);
    box-shadow: 0 2px 8px rgba(30, 58, 138, 0.15);
}

.nota-input-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
    align-items: center;
}

.nota-input {
    width: 70px;
    padding: 8px 4px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    text-align: center;
    font-size: 1em;
    font-weight: 600;
    color: #495057;
    background: white;
    transition: all 0.3s ease;
    /* Quitar flechas de input number */
    -webkit-appearance: none;
    -moz-appearance: textfield;
    appearance: textfield;
}

.nota-input:focus {
    outline: none;
    border-color: #1e3a8a;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    transform: translateY(-1px);
}

/* Quitar flechas de input number - reglas adicionales */
.nota-input::-webkit-outer-spin-button,
.nota-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.nota-input[type=number] {
    -moz-appearance: textfield;
}

.obs-input {
    width: 70px;
    padding: 4px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    text-align: center;
    font-size: 0.8em;
    color: #6c757d;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.obs-input:focus {
    outline: none;
    border-color: #1e3a8a;
    background: white;
}

.nota-display {
    text-align: center;
    padding: 8px 4px;
}

.nota-value {
    font-weight: 700;
    font-size: 1.2em;
    color: #495057;
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-width: 40px;
    transition: all 0.3s ease;
}



.obs-display {
    color: #6c757d;
    font-style: italic;
    font-size: 0.8em;
    margin-top: 4px;
    padding: 2px 6px;
    background: rgba(108, 117, 125, 0.1);
    border-radius: 4px;
    display: inline-block;
}

.nota-form {
    margin: 0;
}

.boletin-table tbody tr {
    transition: all 0.3s ease;
}

.boletin-table tbody tr:hover {
    background: linear-gradient(135deg, #f0f4ff 0%, #dbeafe 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.1);
}

.boletin-table tbody tr:hover .estudiante-cell {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.boletin-table tbody tr {
    animation: fadeIn 0.5s ease-out;
}

.boletin-table tbody tr:nth-child(even) {
    animation-delay: 0.1s;
}

.boletin-table tbody tr:nth-child(odd) {
    animation-delay: 0.2s;
}

/* Responsive */
@media (max-width: 768px) {
    .materia-col {
        min-width: 90px;
        font-size: 0.8em;
        height: 120px;
    }
    
    .estudiante-col {
        min-width: 160px;
    }
    
    .nota-input {
        width: 60px;
        font-size: 0.9em;
        padding: 6px 3px;
        /* Quitar flechas de input number en móvil */
        -webkit-appearance: none;
        -moz-appearance: textfield;
        appearance: textfield;
    }
    
    /* Quitar flechas de input number en móvil - reglas adicionales */
    .nota-input::-webkit-outer-spin-button,
    .nota-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    .nota-input[type=number] {
        -moz-appearance: textfield;
    }
    
    .obs-input {
        width: 60px;
        font-size: 0.75em;
        padding: 3px;
    }
    
    .nota-value {
        font-size: 1.1em;
        min-width: 35px;
    }
    
    .nota-value.nota-final-value {
    font-size: 1.2em;
}

.nota-pendiente {
    font-weight: 600;
    font-size: 0.9em;
    color: #6c757d;
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    font-style: italic;
}
}

/* Scrollbar personalizado */
.boletin-container::-webkit-scrollbar {
    height: 8px;
}

.boletin-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.boletin-container::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    border-radius: 4px;
}

.boletin-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
}

/* Estilos para columnas de cuatrimestre */
.cuatrimestre-col {
    background: #f8f9fa;
    font-size: 0.85em;
    font-weight: 600;
    color: var(--secondary-color);
    min-width: 80px;
    border-bottom: 2px solid var(--primary-color);
}

.materia-col {
    text-align: center;
    background: var(--primary-color);
    color: white;
    font-weight: 600;
    border-bottom: 2px solid var(--primary-dark);
}

/* Ajustar inputs para tablas más compactas */
.nota-input {
    width: 60px;
    font-size: 0.9em;
}

.obs-input {
    width: 70px;
    font-size: 0.8em;
}

/* Responsive para nueva estructura */
@media (max-width: 1200px) {
    .cuatrimestre-col {
        min-width: 70px;
        font-size: 0.75em;
    }
    
    .nota-input {
        width: 50px;
        font-size: 0.85em;
    }
    
    .obs-input {
        width: 60px;
        font-size: 0.75em;
    }
}
</style>

<?php include 'includes/footer.php'; ?> 