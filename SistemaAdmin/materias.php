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

$pageTitle = 'Materias (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';
include 'includes/header.php';

// Verificar permisos (solo admin y directivo) - después del header para tener acceso a hasRole()
if (!(hasRole('admin') || hasRole('directivo'))) {
    header('Location: index.php?error=unauthorized');
    exit();
}

$action = $_GET['action'] ?? '';
$success_message = '';
$error_message = '';

// Crear materia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_materia'])) {
    try {
        $db->query("INSERT INTO materias (nombre, especialidad_id, activa) VALUES (?, ?, 1)", [
            $_POST['nombre'],
            $_POST['especialidad_id'] ?: null
        ]);
        $success_message = 'Materia creada';
        $action = '';
    } catch (Exception $e) {
        $error_message = 'Error al crear materia: ' . $e->getMessage();
    }
}

// Eliminar (desactivar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desactivar_materia'])) {
    try {
        $db->query("UPDATE materias SET activa = 0 WHERE id = ?", [$_POST['materia_id']]);
        $success_message = 'Materia desactivada';
    } catch (Exception $e) {
        $error_message = 'Error al desactivar: ' . $e->getMessage();
    }
}

// Gestionar cursos de materia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gestionar_cursos_materia'])) {
    try {
        $materia_id = $_POST['materia_id'];
        $cursos_seleccionados = $_POST['cursos'] ?? [];
        
        // Eliminar todas las asignaciones actuales de esta materia
        $db->query("DELETE FROM materia_curso WHERE materia_id = ?", [$materia_id]);
        
        // Insertar las nuevas asignaciones
        if (!empty($cursos_seleccionados)) {
            $sql = "INSERT INTO materia_curso (materia_id, curso_id) VALUES (?, ?)";
            foreach ($cursos_seleccionados as $curso_id) {
                $db->query($sql, [$materia_id, $curso_id]);
            }
        }
        
        $success_message = 'Cursos de la materia actualizados correctamente';
        $action = '';
    } catch (Exception $e) {
        $error_message = 'Error al gestionar cursos: ' . $e->getMessage();
    }
}

// Obtener materia para gestión de cursos
$materia_gestionar = null;
if ($action === 'gestionar_cursos' && isset($_GET['id'])) {
    $materia_id = $_GET['id'];
    $materia_gestionar = $db->fetch("
        SELECT m.*, e.nombre as especialidad 
        FROM materias m 
        LEFT JOIN especialidades e ON e.id = m.especialidad_id 
        WHERE m.id = ? AND m.activa = 1
    ", [$materia_id]);
    
    if (!$materia_gestionar) {
        $error_message = 'Materia no encontrada';
        $action = '';
    }
}

$especialidades = $db->fetchAll("SELECT * FROM especialidades WHERE activa = 1 ORDER BY nombre");

// Filtros
$filtro_especialidad = $_GET['filtro_especialidad'] ?? '';
$filtro_tipo = $_GET['filtro_tipo'] ?? '';

// Construir consulta con filtros
$where_conditions = ["m.activa = 1"];
$params = [];

if ($filtro_especialidad !== '') {
    if ($filtro_especialidad === 'sin_especialidad') {
        $where_conditions[] = "m.especialidad_id IS NULL";
    } else {
        $where_conditions[] = "m.especialidad_id = ?";
        $params[] = $filtro_especialidad;
    }
}



$where_clause = implode(" AND ", $where_conditions);

$materias = $db->fetchAll("
    SELECT m.*, e.nombre as especialidad,
           GROUP_CONCAT(CONCAT(c.anio, '°', c.division) ORDER BY c.anio, c.division SEPARATOR ', ') as cursos_asignados,
           COUNT(mc.curso_id) as total_cursos
    FROM materias m 
    LEFT JOIN especialidades e ON e.id = m.especialidad_id 
    LEFT JOIN materia_curso mc ON m.id = mc.materia_id
    LEFT JOIN cursos c ON mc.curso_id = c.id AND c.activo = 1
    WHERE $where_clause
    GROUP BY m.id
    ORDER BY m.nombre
", $params);
?>

<section class="materias-section">
    <div class="section-header">
        <h2>Materias</h2>
        <a href="materias.php?action=nueva" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Materia</a>
    </div>

    <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-error"><?php echo $error_message; ?></div><?php endif; ?>

    <?php if ($action === 'nueva'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Crear Materia</h3></div>
        <form method="POST" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="especialidad_id">Especialidad</label>
                    <select id="especialidad_id" name="especialidad_id">
                        <option value="">Sin especialidad</option>
                        <?php foreach ($especialidades as $esp): ?>
                        <option value="<?php echo $esp['id']; ?>"><?php echo htmlspecialchars($esp['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
            <div class="form-actions">
                <button type="submit" name="guardar_materia" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                <a href="materias.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Formulario gestión de cursos -->
    <?php if ($action === 'gestionar_cursos' && $materia_gestionar): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Gestionar Cursos - <?php echo htmlspecialchars($materia_gestionar['nombre']); ?></h3>
        </div>
        <div class="card-body">
            <div class="materia-info" style="margin-bottom: 2rem; padding: 1rem; background: var(--light-gray); border-radius: var(--border-radius);">
                <h4 style="margin-bottom: 0.5rem;">Información de la Materia</h4>
                <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div><strong>Nombre:</strong> <?php echo htmlspecialchars($materia_gestionar['nombre']); ?></div>
                    <div><strong>Especialidad:</strong> <?php echo htmlspecialchars($materia_gestionar['especialidad'] ?? 'Sin especialidad'); ?></div>

                </div>
            </div>

            <form method="POST" class="form-container">
                <input type="hidden" name="materia_id" value="<?php echo $materia_gestionar['id']; ?>">
                
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Seleccionar Cursos</h4>
                <p style="margin-bottom: 1.5rem; color: var(--secondary-color);">
                    Marca los cursos que deben tener esta materia asignada. 
                    <?php if ($materia_gestionar['especialidad_id']): ?>
                        Solo se muestran cursos de la especialidad: <strong><?php echo htmlspecialchars($materia_gestionar['especialidad']); ?></strong>
                    <?php else: ?>
                        Se muestran todos los cursos activos.
                    <?php endif; ?>
                </p>

                <?php
                // Obtener cursos disponibles
                $where_conditions = ["c.activo = 1"];
                $params = [];
                
                if ($materia_gestionar['especialidad_id']) {
                    $where_conditions[] = "c.especialidad_id = ?";
                    $params[] = $materia_gestionar['especialidad_id'];
                }
                
                $where_clause = implode(" AND ", $where_conditions);
                $cursos_disponibles = $db->fetchAll("
                    SELECT c.*, esp.nombre as especialidad, t.nombre as turno
                    FROM cursos c
                    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                    LEFT JOIN turnos t ON c.turno_id = t.id
                    WHERE $where_clause
                    ORDER BY c.anio, c.division
                ", $params);

                // Obtener cursos actualmente asignados
                $cursos_asignados = $db->fetchAll("
                    SELECT curso_id FROM materia_curso WHERE materia_id = ?
                ", [$materia_gestionar['id']]);
                $cursos_asignados_ids = array_column($cursos_asignados, 'curso_id');
                ?>

                <div class="cursos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <?php foreach ($cursos_disponibles as $curso): ?>
                    <div class="curso-checkbox" style="padding: 1rem; border: 1px solid var(--medium-gray); border-radius: var(--border-radius); background: white;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                            <input type="checkbox" name="cursos[]" value="<?php echo $curso['id']; ?>" 
                                   <?php echo in_array($curso['id'], $cursos_asignados_ids) ? 'checked' : ''; ?>>
                            <div>
                                <strong><?php echo $curso['anio'] . '° ' . $curso['division']; ?></strong>
                                <br><small><?php echo htmlspecialchars($curso['especialidad']); ?> (<?php echo htmlspecialchars($curso['turno']); ?>)</small>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($cursos_disponibles)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No hay cursos disponibles para esta materia.
                    <?php if ($materia_gestionar['especialidad_id']): ?>
                        No existen cursos activos en la especialidad: <strong><?php echo htmlspecialchars($materia_gestionar['especialidad']); ?></strong>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" name="gestionar_cursos_materia" class="btn btn-primary" <?php echo empty($cursos_disponibles) ? 'disabled' : ''; ?>>
                        <i class="fas fa-save"></i> Guardar Asignaciones
                    </button>
                    <a href="materias.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filtros de Búsqueda</h3>
        </div>
        <form method="GET" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="filtro_especialidad">Especialidad:</label>
                    <select id="filtro_especialidad" name="filtro_especialidad">
                        <option value="">Todas las especialidades</option>
                        <option value="sin_especialidad" <?php echo $filtro_especialidad === 'sin_especialidad' ? 'selected' : ''; ?>>Sin especialidad</option>
                        <?php foreach ($especialidades as $esp): ?>
                        <option value="<?php echo $esp['id']; ?>" <?php echo $filtro_especialidad == $esp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($esp['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                

            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="materias.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de materias -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Materias Registradas (<?php echo number_format(count($materias)); ?>)</h3>
        </div>
        <div class="table-container">
            <?php if ($filtro_especialidad !== '' || $filtro_tipo !== ''): ?>
            <div style="margin-bottom: 1rem; padding: 0.75rem; background: var(--light-gray); border-radius: var(--border-radius); font-size: 0.875rem;">
                <i class="fas fa-filter"></i> 
                Mostrando <strong><?php echo count($materias); ?></strong> materia<?php echo count($materias) != 1 ? 's' : ''; ?>
                <?php if ($filtro_especialidad !== ''): ?>
                    <?php if ($filtro_especialidad === 'sin_especialidad'): ?>
                        sin especialidad
                    <?php else: ?>
                        de la especialidad: <strong><?php echo htmlspecialchars($especialidades[array_search($filtro_especialidad, array_column($especialidades, 'id'))]['nombre'] ?? ''); ?></strong>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
            <?php endif; ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Especialidad</th>

                        <th>Cursos Asignados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materias as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($m['especialidad'] ?? '-'); ?></td>

                        <td>
                            <?php if ($m['total_cursos'] > 0): ?>
                                <div style="font-size: 0.875rem;">
                                    <strong><?php echo $m['total_cursos']; ?> curso<?php echo $m['total_cursos'] != 1 ? 's' : ''; ?></strong>
                                    <br><small><?php echo htmlspecialchars($m['cursos_asignados']); ?></small>
                                </div>
                            <?php else: ?>
                                <span class="status status-warning" style="font-size: 0.75rem;">Sin asignar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="materias.php?action=gestionar_cursos&id=<?php echo $m['id']; ?>" 
                                   class="btn btn-warning btn-sm" title="Gestionar cursos">
                                    <i class="fas fa-users"></i> Cursos
                                </a>
                                <form method="POST" onsubmit="return confirm('¿Desactivar materia?');" style="display: inline;">
                                    <input type="hidden" name="materia_id" value="<?php echo $m['id']; ?>">
                                    <button type="submit" name="desactivar_materia" class="btn btn-danger btn-sm" title="Desactivar materia">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<style>
/* Estilos para checkbox */
input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-color);
    cursor: pointer;
}

input[type="checkbox"]:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Estilos para gestión de cursos */
.curso-checkbox {
    transition: all 0.3s ease;
    border: 1px solid var(--medium-gray);
}

.curso-checkbox:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-color);
}

.curso-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-color);
}

.curso-checkbox label {
    transition: all 0.3s ease;
}

.curso-checkbox input[type="checkbox"]:checked + div {
    color: var(--primary-color);
    font-weight: 600;
}

/* Estilos para botones */
.btn-warning {
    transition: all 0.3s ease;
}

.btn-warning:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.btn-danger {
    transition: all 0.3s ease;
}

.btn-danger:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
}

/* Animaciones */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.alert {
    animation: slideIn 0.3s ease-out;
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

/* Responsive */
@media (max-width: 768px) {
    .cursos-grid {
        grid-template-columns: 1fr !important;
    }
    
    .info-grid {
        grid-template-columns: 1fr !important;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?> 