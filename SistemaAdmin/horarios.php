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

$pageTitle = 'Horarios (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';
include 'includes/header.php';

use SistemaAdmin\Controllers\HorariosController;

// Inicializar servicios
$horariosController = new HorariosController($servicioAutenticacion, $db);

$action = $_GET['action'] ?? '';
$curso_filter = $_GET['curso'] ?? '';
$profesor_filter = $_GET['profesor'] ?? '';
$success_message = '';
$error_message = '';

// Solo admin y directivo pueden crear horarios
if ($action === 'nuevo' && !$servicioAutenticacion->tienePermiso('gestionar_horarios')) {
    header('Location: horarios.php?error=unauthorized');
    exit();
}

// Procesar formulario de nuevo horario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_horario'])) {
    $resultado = $horariosController->crear([
        'curso_id' => $_POST['curso_id'],
        'materia_id' => $_POST['materia_id'],
        'dia_semana' => $_POST['dia_semana'],
        'hora_inicio' => $_POST['hora_inicio'],
        'hora_fin' => $_POST['hora_fin'],
        'aula' => $_POST['aula'] ?? null,
        'docente' => $_POST['docente'] ?? null,
        'es_contraturno' => isset($_POST['es_contraturno'])
    ]);
    
    if ($resultado['success']) {
        $success_message = $resultado['message'];
        $action = '';
    } else {
        $error_message = $resultado['error'];
    }
}

// Procesar formulario de edición de horario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_horario'])) {
    $resultado = $horariosController->actualizar($_POST['horario_id'], [
        'curso_id' => $_POST['curso_id'],
        'materia_id' => $_POST['materia_id'],
        'dia_semana' => $_POST['dia_semana'],
        'hora_inicio' => $_POST['hora_inicio'],
        'hora_fin' => $_POST['hora_fin'],
        'aula' => $_POST['aula'] ?? null,
        'docente' => $_POST['docente'] ?? null,
        'es_contraturno' => isset($_POST['es_contraturno'])
    ]);
    
    if ($resultado['success']) {
        $success_message = $resultado['message'];
        $action = '';
    } else {
        $error_message = $resultado['error'];
    }
}

// Procesar eliminación de horario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_horario'])) {
    $resultado = $horariosController->eliminar($_POST['horario_id']);
    
    if ($resultado['success']) {
        $success_message = $resultado['message'];
    } else {
        $error_message = $resultado['error'];
    }
}

// Obtener horario para edición
$horario_editar = null;
if ($action === 'editar' && isset($_GET['id'])) {
    $resultado = $horariosController->obtener($_GET['id']);
    if ($resultado['success']) {
        $horario_editar = $resultado['data'];
    } else {
        $error_message = $resultado['error'];
        $action = '';
    }
}

// Obtener horarios con filtros
$filtros = [];
if ($curso_filter) $filtros['curso'] = $curso_filter;
if ($profesor_filter) $filtros['profesor'] = $profesor_filter;

$resultado_horarios = $horariosController->listar($filtros);
$horarios = $resultado_horarios['success'] ? $resultado_horarios['data'] : [];

// Obtener datos para formularios
$resultado_formularios = $horariosController->obtenerDatosFormularios();
$cursos = $resultado_formularios['success'] ? $resultado_formularios['data']['cursos'] : [];
$materias = $resultado_formularios['success'] ? $resultado_formularios['data']['materias'] : [];
$profesores_filtro = $resultado_formularios['success'] ? $resultado_formularios['data']['profesores'] : [];

// Obtener profesores por curso para filtro dinámico
$profesores_por_curso = [];
foreach ($cursos as $curso) {
    $profesores_curso = $db->fetchAll("
        SELECT DISTINCT h.docente
        FROM horarios h
        WHERE h.curso_id = ? AND h.docente IS NOT NULL AND h.docente != ''
        ORDER BY h.docente
    ", [$curso['id']]);
    
    $profesores_por_curso[$curso['id']] = $profesores_curso;
}

// Función para obtener profesores por materia
function getProfesoresPorMateria($db, $materia_id) {
    if (!$materia_id) return [];
    
    return $db->fetchAll("
        SELECT DISTINCT p.id, p.apellido, p.nombre
        FROM profesores p
        JOIN profesor_materia pm ON p.id = pm.profesor_id
        WHERE pm.materia_id = ? AND pm.activo = 1 AND p.activo = 1
        ORDER BY p.apellido, p.nombre
    ", [$materia_id]);
}

// Estadísticas
$total_horarios = count($horarios);
$contraturno_count = count(array_filter($horarios, function($h) { return $h['es_contraturno']; }));

$dias_semana = [
    1 => 'Lunes',
    2 => 'Martes', 
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado'
];
?>

<section class="horarios-section">
    <div class="section-header">
        <h2>Gestión de Horarios</h2>
        <?php if ($servicioAutenticacion->tienePermiso('gestionar_horarios')): ?>
        <a href="horarios.php?action=nuevo" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Horario
        </a>
        <?php endif; ?>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            No tienes permisos para crear horarios.
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_horarios); ?></h3>
                <p>Total Horarios</p>
            </div>
        </div>
        
        <?php if ($contraturno_count > 0): ?>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-moon"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($contraturno_count); ?></h3>
                <p>Contraturno</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Formulario nuevo horario -->
    <?php if ($action === 'nuevo'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Registrar Nuevo Horario</h3>
        </div>
        <form method="POST" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="curso_id">Curso: *</label>
                    <select name="curso_id" id="curso_id" required>
                        <option value="">Seleccionar curso</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>">
                            <?php 
                            if ($curso['anio'] <= 3) {
                                // Cursos inferiores: solo año y división
                                echo $curso['anio'] . '° ' . $curso['division'] . ' (' . $curso['turno'] . ')';
                            } else {
                                // Cursos superiores: año, división y especialidad
                                echo $curso['anio'] . '° ' . $curso['division'] . ' - ' . $curso['especialidad'] . ' (' . $curso['turno'] . ')';
                            }
                            ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="materia_id">Materia: *</label>
                    <select name="materia_id" id="materia_id" required>
                        <option value="">Seleccionar materia</option>
                        <?php foreach ($materias as $materia): ?>
                        <option value="<?php echo $materia['id']; ?>">
                            <?php echo htmlspecialchars($materia['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="dia_semana">Día de la Semana: *</label>
                    <select name="dia_semana" id="dia_semana" required>
                        <option value="">Seleccionar día</option>
                        <?php foreach ($dias_semana as $num => $nombre): ?>
                        <option value="<?php echo $num; ?>"><?php echo $nombre; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="hora_inicio">Hora de Inicio: *</label>
                    <input type="time" name="hora_inicio" id="hora_inicio" required>
                </div>
                
                <div class="form-group">
                    <label for="hora_fin">Hora de Fin: *</label>
                    <input type="time" name="hora_fin" id="hora_fin" required>
                </div>
                
                <div class="form-group">
                    <label for="aula">Aula:</label>
                    <input type="text" name="aula" id="aula" maxlength="50" placeholder="Ej: Aula 1, Lab. Informática">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="docente">Docente:</label>
                    <select name="docente" id="docente">
                        <option value="">Seleccionar profesor (primero selecciona curso y materia)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="es_contraturno" id="es_contraturno">
                        Es Contraturno
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="guardar_horario" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Horario
                </button>
                <a href="horarios.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Formulario editar horario -->
    <?php if ($action === 'editar' && $horario_editar): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Editar Horario</h3>
        </div>
        <form method="POST" class="form-container">
            <input type="hidden" name="horario_id" value="<?php echo $horario_editar['id']; ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="curso_id_edit">Curso: *</label>
                    <select name="curso_id" id="curso_id_edit" required>
                        <option value="">Seleccionar curso</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>" 
                                <?php echo $horario_editar['curso_id'] == $curso['id'] ? 'selected' : ''; ?>>
                            <?php 
                            if ($curso['anio'] <= 3) {
                                // Cursos inferiores: solo año y división
                                echo $curso['anio'] . '° ' . $curso['division'] . ' (' . $curso['turno'] . ')';
                            } else {
                                // Cursos superiores: año, división y especialidad
                                echo $curso['anio'] . '° ' . $curso['division'] . ' - ' . $curso['especialidad'] . ' (' . $curso['turno'] . ')';
                            }
                            ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="materia_id_edit">Materia: *</label>
                    <select name="materia_id" id="materia_id_edit" required>
                        <option value="">Seleccionar materia</option>
                        <?php foreach ($materias as $materia): ?>
                        <option value="<?php echo $materia['id']; ?>" 
                                <?php echo $horario_editar['materia_id'] == $materia['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($materia['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="dia_semana_edit">Día de la Semana: *</label>
                    <select name="dia_semana" id="dia_semana_edit" required>
                        <option value="">Seleccionar día</option>
                        <?php foreach ($dias_semana as $num => $nombre): ?>
                        <option value="<?php echo $num; ?>" 
                                <?php echo $horario_editar['dia_semana'] == $num ? 'selected' : ''; ?>>
                            <?php echo $nombre; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="hora_inicio_edit">Hora de Inicio: *</label>
                    <input type="time" name="hora_inicio" id="hora_inicio_edit" 
                           value="<?php echo $horario_editar['hora_inicio']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="hora_fin_edit">Hora de Fin: *</label>
                    <input type="time" name="hora_fin" id="hora_fin_edit" 
                           value="<?php echo $horario_editar['hora_fin']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="aula_edit">Aula:</label>
                    <input type="text" name="aula" id="aula_edit" maxlength="50" 
                           value="<?php echo htmlspecialchars($horario_editar['aula'] ?? ''); ?>" 
                           placeholder="Ej: Aula 1, Lab. Informática">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="docente_edit">Docente:</label>
                    <select name="docente" id="docente_edit">
                        <option value="">Seleccionar profesor (primero selecciona curso y materia)</option>
                        <?php 
                        if ($horario_editar['materia_id'] && $horario_editar['curso_id']) {
                            $profesores_materia = $db->fetchAll("
                                SELECT DISTINCT p.id, p.apellido, p.nombre
                                FROM profesores p
                                JOIN profesor_materia pm ON p.id = pm.profesor_id
                                JOIN profesor_curso pc ON p.id = pc.profesor_id
                                WHERE pm.materia_id = ? 
                                AND pc.curso_id = ? 
                                AND pm.activo = 1 
                                AND pc.activo = 1 
                                AND p.activo = 1
                                AND pc.anio_academico = YEAR(CURDATE())
                                AND NOT EXISTS (
                                    -- Verificar que no haya otro profesor con el mismo curso y materia
                                    SELECT 1 FROM profesor_materia pm2
                                    JOIN profesor_curso pc2 ON pm2.profesor_id = pc2.profesor_id
                                    WHERE pm2.materia_id = pm.materia_id
                                    AND pm2.profesor_id != p.id
                                    AND pm2.activo = 1
                                    AND pc2.curso_id = pc.curso_id
                                    AND pc2.anio_academico = pc.anio_academico
                                    AND pc2.activo = 1
                                )
                                ORDER BY p.apellido, p.nombre
                            ", [$horario_editar['materia_id'], $horario_editar['curso_id']]);
                            
                            foreach ($profesores_materia as $profesor) {
                                $selected = ($horario_editar['docente'] == $profesor['apellido'] . ' ' . $profesor['nombre']) ? 'selected' : '';
                                echo '<option value="' . $profesor['apellido'] . ' ' . $profesor['nombre'] . '" ' . $selected . '>';
                                echo htmlspecialchars($profesor['apellido'] . ' ' . $profesor['nombre']);
                                echo '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="es_contraturno" id="es_contraturno_edit" 
                               <?php echo $horario_editar['es_contraturno'] ? 'checked' : ''; ?>>
                        Es Contraturno
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="actualizar_horario" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Horario
                </button>
                <a href="horarios.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filtros</h3>
        </div>
        <form method="GET" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="curso">Curso:</label>
                    <select name="curso" id="curso">
                        <option value="">Todos los cursos</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>" 
                                <?php echo $curso_filter == $curso['id'] ? 'selected' : ''; ?>>
                            <?php 
                            if ($curso['anio'] <= 3) {
                                // Cursos inferiores: solo año y división
                                echo $curso['anio'] . '° ' . $curso['division'];
                            } else {
                                // Cursos superiores: año, división y especialidad
                                echo $curso['anio'] . '° ' . $curso['division'] . ' - ' . $curso['especialidad'];
                            }
                            ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="profesor">Profesor:</label>
                    <select name="profesor" id="profesor">
                        <option value="">Todos los profesores</option>
                        <?php foreach ($profesores_filtro as $profesor): ?>
                        <option value="<?php echo htmlspecialchars($profesor['docente']); ?>" 
                                <?php echo $profesor_filter == $profesor['docente'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($profesor['docente']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="horarios.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Vista de horarios -->
    <?php if (!empty($horarios)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Horarios Registrados (<?php echo number_format($total_horarios); ?>)</h3>
        </div>
        
        <!-- Vista por curso -->
        <?php
        $horarios_por_curso = [];
        foreach ($horarios as $horario) {
            $curso_key = $horario['anio'] . '° ' . $horario['division'] . ' - ' . $horario['especialidad'];
            $horarios_por_curso[$curso_key][] = $horario;
        }
        ?>
        
        <div class="card-body">
            <?php foreach ($horarios_por_curso as $curso_nombre => $horarios_curso): ?>
            <div class="curso-horarios-container">
                <div class="curso-header">
                    <h4>
                        <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($curso_nombre); ?>
                        <span class="status status-primary">
                            <?php echo $horarios_curso[0]['turno']; ?>
                        </span>
                    </h4>
                </div>
                
                <!-- Tabla de horarios semanal -->
                <div class="horario-semanal" style="overflow-x: auto;">
                    <table class="table" style="min-width: 800px;">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Horario</th>
                                <?php foreach ($dias_semana as $dia): ?>
                                <th><?php echo $dia; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Agrupar por hora de inicio
                            $horarios_por_hora = [];
                            foreach ($horarios_curso as $h) {
                                $hora_key = $h['hora_inicio'] . '-' . $h['hora_fin'];
                                $horarios_por_hora[$hora_key][$h['dia_semana']] = $h;
                            }
                            
                            foreach ($horarios_por_hora as $hora_key => $horarios_hora):
                                list($hora_inicio, $hora_fin) = explode('-', $hora_key);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('H:i', strtotime($hora_inicio)); ?></strong><br>
                                    <small><?php echo date('H:i', strtotime($hora_fin)); ?></small>
                                </td>
                                <?php foreach ($dias_semana as $dia_num => $dia_nombre): ?>
                                <td>
                                    <?php if (isset($horarios_hora[$dia_num])): 
                                        $h = $horarios_hora[$dia_num];
                                    ?>
                                        <div class="materia-cell" style="padding: 0.5rem; border-left: 3px solid var(--primary-color); background: var(--light-gray); position: relative;">
                                            <strong><?php echo htmlspecialchars($h['materia']); ?></strong>

                                            <?php if ($h['es_contraturno']): ?>
                                                <span class="status status-danger" style="font-size: 0.7rem;">Contraturno</span>
                                            <?php endif; ?>
                                            <?php if ($h['aula']): ?>
                                                <br><small><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($h['aula']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($h['docente']): ?>
                                                <br><small><i class="fas fa-user"></i> <?php echo htmlspecialchars($h['docente']); ?></small>
                                                
                                                <?php if ($h['suplencia_estado'] === 'activa'): ?>
                                                    <?php if ($h['fuera_servicio']): ?>
                                                        <br><small><span class="status status-danger" style="font-size: 0.6rem;"><i class="fas fa-ban"></i> Fuera de Servicio</span></small>
                                                    <?php else: ?>
                                                        <br><small><span class="status status-warning" style="font-size: 0.6rem;"><i class="fas fa-user-clock"></i> Suplente: <?php echo htmlspecialchars($h['suplente_apellido'] . ' ' . $h['suplente_nombre']); ?></span></small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <br><small><span class="status status-danger" style="font-size: 0.6rem;">Sin profesor</span></small>
                                            <?php endif; ?>

                                            
                                            <?php if ($servicioAutenticacion->tienePermiso('gestionar_horarios')): ?>
                                            <div style="position: absolute; top: 0.25rem; right: 0.25rem;">
                                                <a href="horarios.php?action=editar&id=<?php echo $h['id']; ?>" 
                                                   class="btn btn-xs btn-warning" title="Editar horario" 
                                                   style="padding: 0.125rem 0.25rem; font-size: 0.6rem;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de que desea eliminar este horario?')">
                                                    <input type="hidden" name="horario_id" value="<?php echo $h['id']; ?>">
                                                    <button type="submit" name="eliminar_horario" class="btn btn-xs btn-danger" title="Eliminar horario" 
                                                            style="padding: 0.125rem 0.25rem; font-size: 0.6rem;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Lista detallada -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Lista Detallada</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Materia</th>
                        <th>Día</th>
                        <th>Horario</th>
                        <th>Aula</th>
                        <th>Docente</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($horarios as $horario): ?>
                    <tr>
                        <td>
                            <strong><?php echo $horario['anio'] . '° ' . $horario['division']; ?></strong>
                            <?php if ($horario['anio'] > 3): ?>
                            <br><small><?php echo htmlspecialchars($horario['especialidad']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($horario['materia']); ?>

                        </td>
                        <td>
                            <i class="fas fa-calendar-day"></i>
                            <?php echo $dias_semana[$horario['dia_semana']]; ?>
                        </td>
                        <td>
                            <strong><?php echo date('H:i', strtotime($horario['hora_inicio'])); ?></strong>
                            -
                            <strong><?php echo date('H:i', strtotime($horario['hora_fin'])); ?></strong>
                        </td>
                        <td>
                            <?php if ($horario['aula']): ?>
                                <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($horario['aula']); ?>
                            <?php else: ?>
                                <span class="status status-warning">No asignada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($horario['docente']): ?>
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($horario['docente']); ?>
                            <?php else: ?>
                                <span class="status status-danger">Sin profesor</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($horario['docente'] && $horario['suplencia_estado'] === 'activa'): ?>
                                <?php if ($horario['fuera_servicio']): ?>
                                    <span class="status status-danger" title="Profesor fuera de servicio">
                                        <i class="fas fa-ban"></i> Fuera de Servicio
                                    </span>
                                <?php else: ?>
                                    <span class="status status-warning" title="Suplente: <?php echo htmlspecialchars($horario['suplente_apellido'] . ' ' . $horario['suplente_nombre']); ?>">
                                        <i class="fas fa-user-clock"></i> Con Suplente
                                    </span>
                                <?php endif; ?>
                            <?php elseif ($horario['es_contraturno']): ?>
                                <span class="status status-danger">Contraturno</span>
                            <?php elseif ($horario['docente']): ?>
                                <span class="status status-success">Regular</span>
                            <?php else: ?>
                                <span class="status status-secondary">Vacío</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($servicioAutenticacion->tienePermiso('gestionar_horarios')): ?>
                            <a href="horarios.php?action=editar&id=<?php echo $horario['id']; ?>" 
                               class="btn btn-sm btn-warning" title="Editar horario">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de que desea eliminar este horario?')">
                                <input type="hidden" name="horario_id" value="<?php echo $horario['id']; ?>">
                                <button type="submit" name="eliminar_horario" class="btn btn-sm btn-danger" title="Eliminar horario">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Estado vacío -->
    <div class="card">
        <div class="card-body text-center" style="padding: 3rem;">
            <i class="fas fa-clock" style="font-size: 4rem; color: var(--secondary-color); opacity: 0.3; margin-bottom: 1rem;"></i>
            <h3 style="color: var(--secondary-color); margin-bottom: 0.5rem;">No hay horarios registrados</h3>
            <p style="color: var(--secondary-color); margin-bottom: 2rem;">
                <?php if ($curso_filter || $profesor_filter): ?>
                    No se encontraron horarios con los filtros seleccionados
                <?php else: ?>
                    Comienza registrando el primer horario de clases
                <?php endif; ?>
            </p>
            <?php if ($servicioAutenticacion->tienePermiso('gestionar_horarios')): ?>
            <a href="horarios.php?action=nuevo" class="btn btn-primary">
                <i class="fas fa-plus"></i> Registrar Primer Horario
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<style>
.horario-semanal .materia-cell {
    min-height: 60px;
    font-size: 0.8rem;
    line-height: 1.3;
}

.horario-semanal td {
    vertical-align: top;
    padding: 0.5rem;
}

.horario-semanal th {
    text-align: center;
    background: var(--primary-color);
    color: white;
    font-weight: 600;
}

/* Separación entre cursos */
.curso-horarios-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    padding: 1.5rem;
    border: 1px solid var(--medium-gray);
}

.curso-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--primary-color);
}

.curso-header h4 {
    color: var(--primary-color);
    margin: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.25rem;
    font-weight: 600;
}

.curso-header .status {
    font-size: 0.75rem;
    margin-left: 1rem;
}

/* Mejorar la visual de las celdas de materia */
.materia-cell {
    border-radius: 6px;
    transition: all 0.2s ease;
}

.materia-cell:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Estilos para botones de editar y eliminar */
.btn-warning, .btn-danger {
    transition: all 0.3s ease;
}

.btn-warning:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.btn-danger:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.btn-xs {
    padding: 0.125rem 0.25rem;
    font-size: 0.6rem;
    border-radius: 0.25rem;
}

/* Animación para formularios de edición */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Estilos para mensajes de confirmación */
.alert {
    animation: slideIn 0.3s ease-out;
}

/* Estilos para estados de suplencias */
.status-danger {
    background-color: #dc3545;
    color: white;
}

.status-warning {
    background-color: #ffc107;
    color: #212529;
}

.status-success {
    background-color: #28a745;
    color: white;
}

.status-secondary {
    background-color: #6c757d;
    color: white;
}

.status {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 0.25rem;
    text-align: center;
}

/* Estilos para filtros dinámicos */
.form-group select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-group select:disabled {
    background-color: #e9ecef;
    opacity: 0.6;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .horario-semanal {
        font-size: 0.75rem;
    }
    
    .materia-cell {
        min-height: 40px !important;
        padding: 0.25rem !important;
    }
    
    .btn-xs {
        padding: 0.1rem 0.2rem;
        font-size: 0.5rem;
    }
}
</style>

<script>
// Función para cargar profesores por materia y curso
function cargarProfesoresPorMateria(materiaId, cursoId, selectDocenteId) {
    const selectDocente = document.getElementById(selectDocenteId);
    
    // Limpiar opciones actuales
    selectDocente.innerHTML = '<option value="">Seleccionar profesor (primero selecciona curso y materia)</option>';
    
    if (!materiaId || !cursoId) {
        return;
    }
    
    // Hacer petición AJAX para obtener profesores
    fetch('profesores.php?ajax=get_profesores_por_materia&materia_id=' + materiaId + '&curso_id=' + cursoId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.profesores) {
                data.profesores.forEach(profesor => {
                    const option = document.createElement('option');
                    option.value = profesor.apellido + ' ' + profesor.nombre;
                    option.textContent = profesor.apellido + ' ' + profesor.nombre;
                    selectDocente.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error al cargar profesores:', error);
        });
}

// Event listeners para formulario nuevo
document.addEventListener('DOMContentLoaded', function() {
    const cursoSelect = document.getElementById('curso_id');
    const materiaSelect = document.getElementById('materia_id');
    const docenteSelect = document.getElementById('docente');
    
    if (cursoSelect && materiaSelect && docenteSelect) {
        // Cuando cambia el curso, limpiar materia y docente
        cursoSelect.addEventListener('change', function() {
            materiaSelect.value = '';
            docenteSelect.innerHTML = '<option value="">Seleccionar profesor (primero selecciona curso y materia)</option>';
        });
        
        // Cuando cambia la materia, cargar profesores
        materiaSelect.addEventListener('change', function() {
            cargarProfesoresPorMateria(this.value, cursoSelect.value, 'docente');
        });
    }
    
    // Event listeners para formulario editar
    const cursoEditSelect = document.getElementById('curso_id_edit');
    const materiaEditSelect = document.getElementById('materia_id_edit');
    const docenteEditSelect = document.getElementById('docente_edit');
    
    if (cursoEditSelect && materiaEditSelect && docenteEditSelect) {
        // Cuando cambia el curso, limpiar materia y docente
        cursoEditSelect.addEventListener('change', function() {
            materiaEditSelect.value = '';
            docenteEditSelect.innerHTML = '<option value="">Seleccionar profesor (primero selecciona curso y materia)</option>';
        });
        
        // Cuando cambia la materia, cargar profesores
        materiaEditSelect.addEventListener('change', function() {
            cargarProfesoresPorMateria(this.value, cursoEditSelect.value, 'docente_edit');
        });
    }
    
    // Event listeners para filtros
    const cursoFiltroSelect = document.getElementById('curso');
    const profesorFiltroSelect = document.getElementById('profesor');
    
    if (cursoFiltroSelect && profesorFiltroSelect) {
        // Datos de profesores por curso (desde PHP)
        const profesoresPorCurso = <?php echo json_encode($profesores_por_curso); ?>;
        
        // Cuando cambia el curso en el filtro, actualizar profesores
        cursoFiltroSelect.addEventListener('change', function() {
            const cursoId = this.value;
            const profesorSelect = document.getElementById('profesor');
            
            // Limpiar opciones actuales
            profesorSelect.innerHTML = '<option value="">Todos los profesores</option>';
            
            if (cursoId && profesoresPorCurso[cursoId]) {
                // Agregar profesores del curso seleccionado
                profesoresPorCurso[cursoId].forEach(profesor => {
                    const option = document.createElement('option');
                    option.value = profesor.docente;
                    option.textContent = profesor.docente;
                    profesorSelect.appendChild(option);
                });
            } else if (!cursoId) {
                // Si no hay curso seleccionado, mostrar todos los profesores
                <?php foreach ($profesores_filtro as $profesor): ?>
                const option = document.createElement('option');
                option.value = '<?php echo htmlspecialchars($profesor['docente']); ?>';
                option.textContent = '<?php echo htmlspecialchars($profesor['docente']); ?>';
                profesorSelect.appendChild(option);
                <?php endforeach; ?>
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
