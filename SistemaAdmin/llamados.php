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

$pageTitle = 'Llamados de Atención (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';

include 'includes/header.php';

use SistemaAdmin\Controllers\LlamadoController;
use SistemaAdmin\Mappers\LlamadoMapper;
use SistemaAdmin\Services\ServicioLlamados;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Services\ServicioEstudiantes;

// Inicializar la nueva arquitectura
$db = Database::getInstance();
$llamadoMapper = new LlamadoMapper($db);
$estudianteMapper = new EstudianteMapper($db);
$servicioEstudiantes = new ServicioEstudiantes($estudianteMapper);
$servicioLlamados = new ServicioLlamados($llamadoMapper, $estudianteMapper);
$llamadoController = new LlamadoController($servicioLlamados, $servicioEstudiantes);

$action = $_GET['action'] ?? '';
$estudiante_id = $_GET['estudiante'] ?? '';

// Procesar formulario de nuevo llamado usando la nueva arquitectura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_llamado'])) {
    try {
        $data = [
            'estudiante_id' => (int)$_POST['estudiante_id'],
            'fecha' => $_POST['fecha'],
            'motivo' => $_POST['motivo'],
            'descripcion' => $_POST['descripcion'],
            'sancion' => $_POST['sancion'] ?? null,
            'usuario_id' => $_SESSION['user_id'] ?? 1 // Por ahora usar ID 1 si no hay sesión
        ];
        
        $resultado = $llamadoController->registrar($data);
        
        if ($resultado['success']) {
            $success_message = "Llamado de atención registrado correctamente";
        } else {
            $error_message = $resultado['error'] ?? 'Error al registrar el llamado';
        }
    } catch (Exception $e) {
        $error_message = "Error al registrar el llamado: " . $e->getMessage();
    }
}

// Filtros para el listado
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$motivo_filter = $_GET['motivo'] ?? '';
$curso_filter = $_GET['curso'] ?? '';
$estudiante_filter = $_GET['estudiante'] ?? '';

// Datos para filtros avanzados
$cursos = $db->fetchAll("
    SELECT c.id, c.anio, c.division, esp.nombre as especialidad 
    FROM cursos c 
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id 
    WHERE c.activo = 1 
    ORDER BY c.anio, c.division
");

$estudiantes_filtro = $db->fetchAll("
    SELECT e.id, e.apellido, e.nombre, c.anio, c.division, esp.nombre as especialidad
    FROM estudiantes e 
    LEFT JOIN cursos c ON e.curso_id = c.id
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    WHERE e.activo = 1 
    ORDER BY e.apellido, e.nombre
");

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

if ($motivo_filter) {
    $where_conditions[] = "l.motivo LIKE ?";
    $params[] = "%$motivo_filter%";
}

if ($curso_filter) {
    $where_conditions[] = "e.curso_id = ?";
    $params[] = $curso_filter;
}

if ($estudiante_filter) {
    $where_conditions[] = "e.id = ?";
    $params[] = $estudiante_filter;
}

if ($estudiante_id) {
    $where_conditions[] = "l.estudiante_id = ?";
    $params[] = $estudiante_id;
}

$where_clause = implode(" AND ", $where_conditions);

// Obtener llamados usando la nueva arquitectura
$llamados_resultado = $llamadoController->recientes(365); // Obtener llamados del último año
$llamados_data = $llamados_resultado['success'] ? $llamados_resultado['data'] : [];

// Aplicar filtros adicionales si es necesario
$llamados = [];
foreach ($llamados_data as $llamado) {
    $incluir = true;
    
    // Filtrar por fecha desde
    if ($fecha_desde && $llamado['fecha'] < $fecha_desde) {
        $incluir = false;
    }
    
    // Filtrar por fecha hasta
    if ($fecha_hasta && $llamado['fecha'] > $fecha_hasta) {
        $incluir = false;
    }
    
    // Filtrar por motivo
    if ($motivo_filter && strpos($llamado['motivo'], $motivo_filter) === false) {
        $incluir = false;
    }
    
    // Filtrar por curso
    if ($curso_filter && isset($llamado['curso_id']) && $llamado['curso_id'] != $curso_filter) {
        $incluir = false;
    }
    
    // Filtrar por estudiante
    if ($estudiante_filter && $llamado['estudiante_id'] != $estudiante_filter) {
        $incluir = false;
    }
    
    if ($incluir) {
        $llamados[] = $llamado;
    }
}

// Obtener estudiantes para formulario con información de curso
$estudiantes = $estudiantes_filtro; // Usar la consulta que ya incluye curso y especialidad

// Estadísticas básicas usando la nueva arquitectura
$total_llamados = count($llamados);

$llamados_hoy = 0;
$llamados_con_sancion = 0;
$llamados_sin_sancion = 0;

foreach ($llamados as $llamado) {
    // Contar llamados de hoy
    if ($llamado['fecha'] === date('Y-m-d')) {
        $llamados_hoy++;
    }
    
    // Contar llamados con/sin sanción
    if (!empty($llamado['sancion'])) {
        $llamados_con_sancion++;
    } else {
        $llamados_sin_sancion++;
    }
}

// Estadísticas avanzadas para análisis usando la nueva arquitectura
$estudiantes_con_llamados = count(array_unique(array_column($llamados, 'estudiante_id')));

// Motivos más frecuentes
$motivos_frecuentes = [];
foreach ($llamados as $llamado) {
    $motivo = $llamado['motivo'];
    if (!isset($motivos_frecuentes[$motivo])) {
        $motivos_frecuentes[$motivo] = 0;
    }
    $motivos_frecuentes[$motivo]++;
}
arsort($motivos_frecuentes);
$motivos_frecuentes = array_slice(array_map(function($motivo, $cantidad) {
    return ['motivo' => $motivo, 'cantidad' => $cantidad];
}, array_keys($motivos_frecuentes), array_values($motivos_frecuentes)), 0, 10);

// Sanciones más aplicadas
$sanciones_frecuentes = [];
foreach ($llamados as $llamado) {
    if (!empty($llamado['sancion'])) {
        $sancion = $llamado['sancion'];
        if (!isset($sanciones_frecuentes[$sancion])) {
            $sanciones_frecuentes[$sancion] = 0;
        }
        $sanciones_frecuentes[$sancion]++;
    }
}
arsort($sanciones_frecuentes);
$sanciones_frecuentes = array_slice(array_map(function($sancion, $cantidad) {
    return ['sancion' => $sancion, 'cantidad' => $cantidad];
}, array_keys($sanciones_frecuentes), array_values($sanciones_frecuentes)), 0, 10);
?>

<section class="llamados-section">
    <div class="section-header">
        <h2>Gestión de Llamados de Atención</h2>
        <a href="llamados.php?action=nuevo" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Llamado
        </a>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_llamados); ?></h3>
                <p>Total Llamados</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $llamados_hoy; ?></h3>
                <p>Llamados Hoy</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($llamados_con_sancion); ?></h3>
                <p>Con Sanción</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($llamados_sin_sancion); ?></h3>
                <p>Sin Sanción</p>
            </div>
        </div>
    </div>

    <!-- Formulario de nuevo llamado -->
    <?php if ($action === 'nuevo' || $estudiante_id): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Registrar Nuevo Llamado de Atención</h3>
        </div>
        <form method="POST" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="estudiante_id">Estudiante:</label>
                    <select name="estudiante_id" id="estudiante_id" required>
                        <option value="">Seleccionar estudiante</option>
                        <?php foreach ($estudiantes as $est): ?>
                        <option value="<?php echo $est['id']; ?>" 
                                <?php echo $estudiante_id == $est['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($est['apellido'] . ', ' . $est['nombre'] . ' - ' . $est['anio'] . '° ' . $est['division'] . ' ' . $est['especialidad']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fecha">Fecha:</label>
                    <input type="date" name="fecha" id="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="motivo">Motivo:</label>
                    <select name="motivo" id="motivo" required>
                        <option value="">Seleccionar motivo</option>
                        <option value="Falta de respeto al docente">Falta de respeto al docente</option>
                        <option value="Uso inadecuado de dispositivos">Uso inadecuado de dispositivos</option>
                        <option value="Agresión verbal a compañero">Agresión verbal a compañero</option>
                        <option value="Agresión física">Agresión física</option>
                        <option value="Falta de material escolar">Falta de material escolar</option>
                        <option value="No cumplir con tareas">No cumplir con tareas</option>
                        <option value="Conducta inadecuada en clase">Conducta inadecuada en clase</option>
                        <option value="Abandono del aula sin autorización">Abandono del aula sin autorización</option>
                        <option value="Vandalismo">Vandalismo</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="descripcion">Descripción del Hecho:</label>
                    <textarea name="descripcion" id="descripcion" placeholder="Describir detalladamente lo ocurrido" required></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="sancion">Sanción Aplicada:</label>
                    <select name="sancion" id="sancion">
                        <option value="">Sin sanción</option>
                        <option value="Amonestación verbal">Amonestación verbal</option>
                        <option value="Amonestación escrita">Amonestación escrita</option>
                        <option value="Suspensión 1 día">Suspensión 1 día</option>
                        <option value="Suspensión 3 días">Suspensión 3 días</option>
                        <option value="Suspensión 5 días">Suspensión 5 días</option>
                        <option value="Suspensión 10 días">Suspensión 10 días</option>
                        <option value="Citación a padres">Citación a padres</option>
                        <option value="Derivación a gabinete">Derivación a gabinete</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="registrar_llamado" class="btn btn-primary">
                    <i class="fas fa-save"></i> Registrar Llamado
                </button>
                <a href="llamados.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Filtros para el listado -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filtros de Búsqueda</h3>
        </div>
        <form method="GET" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="curso">Curso:</label>
                    <select name="curso" id="curso">
                        <option value="">Todos los cursos</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>" <?php echo $curso_filter == $curso['id'] ? 'selected' : ''; ?>>
                            <?php echo $curso['anio'] . '° ' . $curso['division'] . ' - ' . $curso['especialidad']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="estudiante">Estudiante:</label>
                    <select name="estudiante" id="estudiante">
                        <option value="">Todos los estudiantes</option>
                        <?php foreach ($estudiantes_filtro as $est): ?>
                        <option value="<?php echo $est['id']; ?>" <?php echo $estudiante_filter == $est['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($est['apellido'] . ', ' . $est['nombre']) . 
                                      ($est['anio'] ? ' - ' . $est['anio'] . '° ' . $est['division'] : ''); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fecha_desde">Fecha Desde:</label>
                    <input type="date" name="fecha_desde" id="fecha_desde" 
                           value="<?php echo htmlspecialchars($fecha_desde); ?>">
                </div>
                
                <div class="form-group">
                    <label for="fecha_hasta">Fecha Hasta:</label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" 
                           value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                </div>
                
                <div class="form-group">
                    <label for="motivo_filter">Motivo:</label>
                    <input type="text" name="motivo" id="motivo_filter" 
                           value="<?php echo htmlspecialchars($motivo_filter); ?>" 
                           placeholder="Buscar por motivo">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar Reporte
                </button>
                <a href="llamados.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar Filtros
                </a>
                <a href="export_llamados.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Análisis por motivos -->
    <?php if (!empty($motivos_frecuentes)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Motivos Más Frecuentes</h3>
        </div>
        <div class="card-body">
            <?php $total_motivos = array_sum(array_column($motivos_frecuentes, 'cantidad')); ?>
            <?php foreach ($motivos_frecuentes as $motivo): ?>
            <div class="motivo-item" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--medium-gray);">
                <span><?php echo htmlspecialchars($motivo['motivo']); ?></span>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 100px; height: 8px; background: var(--light-gray); border-radius: 4px; overflow: hidden;">
                        <div style="width: <?php echo ($motivo['cantidad'] / $total_motivos) * 100; ?>%; height: 100%; background: var(--warning-color);"></div>
                    </div>
                    <span class="status status-warning"><?php echo number_format($motivo['cantidad']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Análisis por sanciones -->
    <?php if (!empty($sanciones_frecuentes)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sanciones Más Aplicadas</h3>
        </div>
        <div class="card-body">
            <?php $total_sanciones = array_sum(array_column($sanciones_frecuentes, 'cantidad')); ?>
            <?php foreach ($sanciones_frecuentes as $sancion): ?>
            <div class="sancion-item" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--medium-gray);">
                <span><?php echo htmlspecialchars($sancion['sancion']); ?></span>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 100px; height: 8px; background: var(--light-gray); border-radius: 4px; overflow: hidden;">
                        <div style="width: <?php echo ($sancion['cantidad'] / $total_sanciones) * 100; ?>%; height: 100%; background: var(--danger-color);"></div>
                    </div>
                    <span class="status status-danger"><?php echo number_format($sancion['cantidad']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lista de llamados -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detalle de Llamados de Atención (<?php echo number_format($total_llamados); ?>)</h3>
        </div>
        
        <?php if (!empty($llamados)): ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Estudiante</th>
                        <th>Curso</th>
                        <th>Motivo</th>
                        <th>Sanción</th>
                        <th>Registrado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($llamados as $llamado): ?>
                    <tr>
                        <td>
                            <strong><?php echo date('d/m/Y', strtotime($llamado['fecha'])); ?></strong>
                            <br><small><?php echo strftime('%A', strtotime($llamado['fecha'])); ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($llamado['apellido'] . ', ' . $llamado['nombre']); ?></strong>
                            <br><small>DNI: <?php echo htmlspecialchars($llamado['dni']); ?></small>
                        </td>
                        <td>
                            <?php echo $llamado['anio'] . '° ' . $llamado['division']; ?>
                            <br><small><?php echo htmlspecialchars($llamado['especialidad']); ?></small>
                        </td>
                        <td>
                            <span class="status status-warning">
                                <?php echo htmlspecialchars($llamado['motivo']); ?>
                            </span>
                            <?php if (!empty($llamado['descripcion'])): ?>
                                <br><small style="color: var(--secondary-color);" 
                                          title="<?php echo htmlspecialchars($llamado['descripcion']); ?>">
                                    <?php echo htmlspecialchars(substr($llamado['descripcion'], 0, 50)) . (strlen($llamado['descripcion']) > 50 ? '...' : ''); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($llamado['sancion']): ?>
                                <span class="status status-danger"><?php echo htmlspecialchars($llamado['sancion']); ?></span>
                            <?php else: ?>
                                <span class="status status-success">Sin sanción</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small>
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($llamado['usuario_apellido'] . ', ' . $llamado['usuario_nombre']); ?>
                                <br><?php echo date('d/m/Y H:i', strtotime($llamado['fecha_registro'])); ?>
                            </small>
                        </td>
                        <td>
                            <a href="estudiante_ficha.php?id=<?php echo $llamado['estudiante_id']; ?>" 
                               class="btn btn-sm btn-primary" title="Ver ficha del estudiante">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php else: ?>
        <div class="card-body text-center" style="padding: 3rem;">
            <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: var(--secondary-color); opacity: 0.3; margin-bottom: 1rem;"></i>
            <h3 style="color: var(--secondary-color); margin-bottom: 0.5rem;">No hay llamados de atención registrados</h3>
            <p style="color: var(--secondary-color); margin-bottom: 2rem;">
                <?php if ($fecha_desde || $motivo_filter): ?>
                    No se encontraron llamados con los criterios especificados
                <?php else: ?>
                    Aún no se han registrado llamados de atención
                <?php endif; ?>
            </p>
            <a href="llamados.php?action=nuevo" class="btn btn-primary">
                <i class="fas fa-plus"></i> Registrar Primer Llamado
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Configurar locale español */
<?php setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'Spanish'); ?>

/* Estilos para análisis de reportes */
.motivo-item:last-child,
.sancion-item:last-child {
    border-bottom: none !important;
}

.motivo-item:hover,
.sancion-item:hover {
    background-color: var(--light-gray);
    border-radius: 4px;
    padding: 0.75rem !important;
    margin: 0 -0.75rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr !important;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
