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

$pageTitle = 'Materias Previas (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';
include 'includes/header.php';

// Verificar permisos (solo admin y directivo) - después del header para tener acceso a hasRole()
if (!(hasRole('admin') || hasRole('directivo'))) {
    header('Location: index.php?error=unauthorized');
    exit();
}

$action = $_GET['action'] ?? '';
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_previa'])) {
    try {
        $db->query("INSERT INTO materias_previas (estudiante_id, materia_id, anio_previo, estado, observaciones) VALUES (?, ?, ?, ?, ?)", [
            $_POST['estudiante_id'],
            $_POST['materia_id'],
            $_POST['anio_previo'],
            $_POST['estado'],
            $_POST['observaciones'] ?: null
        ]);
        $success_message = 'Materia previa registrada';
        $action = '';
    } catch (Exception $e) {
        $error_message = 'Error al registrar: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_previa'])) {
    try {
        $db->query("DELETE FROM materias_previas WHERE id = ?", [$_POST['previa_id']]);
        $success_message = 'Registro eliminado';
    } catch (Exception $e) {
        $error_message = 'Error al eliminar: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aprobar_previa'])) {
    try {
        // Obtener información de la materia previa
        $previa = $db->fetch("
            SELECT p.*, e.apellido, e.nombre, m.nombre as materia_nombre, c.anio as anio_actual
            FROM materias_previas p
            JOIN estudiantes e ON e.id = p.estudiante_id
            JOIN materias m ON m.id = p.materia_id
            LEFT JOIN cursos c ON e.curso_id = c.id
            WHERE p.id = ?
        ", [$_POST['previa_id']]);
        
        if (!$previa) {
            throw new Exception('Materia previa no encontrada');
        }
        
        // Registrar nota 7 en todos los cuatrimestres (1, 2, 3) para que aparezca en etapa final
for ($cuatrimestre = 1; $cuatrimestre <= 3; $cuatrimestre++) {
// Verificar si ya existe una nota para este cuatrimestre
            $nota_existente = $db->fetch("
                SELECT id FROM notas 
                WHERE estudiante_id = ? AND materia_id = ? AND cuatrimestre = ?
            ", [$previa['estudiante_id'], $previa['materia_id'], $cuatrimestre]);
            
            if ($nota_existente) {
                // Actualizar la nota existente
                $db->query("
                    UPDATE notas 
                    SET nota = 7, observaciones = 'Aprobada desde materia previa'
                    WHERE id = ?
                ", [$nota_existente['id']]);
            } else {
                // Insertar nueva nota
                $db->query("
                    INSERT INTO notas (estudiante_id, materia_id, cuatrimestre, nota, observaciones, usuario_id)
VALUES (?, ?, ?, 7, 'Aprobada desde materia previa', ?)
                ", [$previa['estudiante_id'], $previa['materia_id'], $cuatrimestre, $_SESSION['usuario_id']]);
            }
        }
        
        // Actualizar el estado de la materia previa a "aprobada"
        $db->query("
            UPDATE materias_previas 
            SET estado = 'aprobada', observaciones = 'Aprobada desde materia previa'
            WHERE id = ?
        ", [$_POST['previa_id']]);
        
        $success_message = 'Materia previa aprobada. Se registró nota 7 en el 3° cuatrimestre para la etapa final.';
    } catch (Exception $e) {
        $error_message = 'Error al aprobar: ' . $e->getMessage();
    }
}

// Filtros
$curso_filter = $_GET['curso'] ?? '';
$estudiante_filter = $_GET['estudiante'] ?? '';

// Datos para filtros
$cursos = $db->fetchAll("
    SELECT c.id, c.anio, c.division, esp.nombre as especialidad 
    FROM cursos c 
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id 
    WHERE c.activo = 1 
    ORDER BY c.anio, c.division
");

$estudiantes = $db->fetchAll("
    SELECT e.id, e.apellido, e.nombre, c.anio, c.division, esp.nombre as especialidad
    FROM estudiantes e 
    LEFT JOIN cursos c ON e.curso_id = c.id
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    WHERE e.activo = 1 
    ORDER BY e.apellido, e.nombre
");

$materias = $db->fetchAll("SELECT * FROM materias WHERE activa = 1 ORDER BY nombre");

// Construir condiciones WHERE para filtros
$where_conditions = ["1=1"];
$params = [];

if ($curso_filter) {
    $where_conditions[] = "e.curso_id = ?";
    $params[] = $curso_filter;
}

if ($estudiante_filter) {
    $where_conditions[] = "e.id = ?";
    $params[] = $estudiante_filter;
}

$where_clause = implode(' AND ', $where_conditions);

$previas = $db->fetchAll("
    SELECT p.*, e.apellido, e.nombre, m.nombre as materia, c.anio as anio_actual, c.division, esp.nombre as especialidad
    FROM materias_previas p
    JOIN estudiantes e ON e.id = p.estudiante_id
    JOIN materias m ON m.id = p.materia_id
    LEFT JOIN cursos c ON e.curso_id = c.id
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    WHERE $where_clause
    ORDER BY e.apellido, e.nombre, p.anio_previo DESC
", $params);
?>

<section class="materias-previas-section">
    <div class="section-header">
        <h2>Materias Previas</h2>
        <a href="materias_previas.php?action=nueva" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Previa</a>
    </div>

    <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-error"><?php echo $error_message; ?></div><?php endif; ?>
    


    <?php if ($action === 'nueva'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Registrar Materia Previa</h3></div>
        <form method="POST" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="estudiante_id">Estudiante *</label>
                    <select id="estudiante_id" name="estudiante_id" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($estudiantes as $e): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['apellido'] . ', ' . $e['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="materia_id">Materia *</label>
                    <select id="materia_id" name="materia_id" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($materias as $m): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="anio_previo">Año</label>
                    <input type="number" min="1" max="7" id="anio_previo" name="anio_previo" value="1" required>
                </div>
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="pendiente">Pendiente</option>
                        <option value="regularizada">Regularizada</option>
                        <option value="aprobada">Aprobada</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <input type="text" id="observaciones" name="observaciones" placeholder="Opcional">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="guardar_previa" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                <a href="materias_previas.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
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
                        <?php foreach ($estudiantes as $est): ?>
                        <option value="<?php echo $est['id']; ?>" <?php echo $estudiante_filter == $est['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($est['apellido'] . ', ' . $est['nombre']) . 
                                      ($est['anio'] ? ' - ' . $est['anio'] . '° ' . $est['division'] : ''); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="materias_previas.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar Filtros
                </a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Listado (<?php echo count($previas); ?> registros)</h3></div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Curso Actual</th>
                        <th>Materia</th>
                        <th>Año Previo</th>
                        <th>Estado</th>
                        <th>Obs.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previas as $p): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['apellido'] . ', ' . $p['nombre']); ?></strong></td>
                        <td>
                            <?php if ($p['anio_actual']): ?>
                                <?php echo $p['anio_actual'] . '° ' . $p['division']; ?>
                                <?php if ($p['especialidad']): ?>
                                    <br><small><?php echo htmlspecialchars($p['especialidad']); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                Sin curso
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['materia']); ?></td>
                        <td><?php echo $p['anio_previo']; ?>°</td>
                        <td>
                            <?php 
                            $estado_class = '';
                            $estado_text = '';
                            switch($p['estado']) {
                                case 'pendiente':
                                    $estado_class = 'status status-warning';
                                    $estado_text = 'Pendiente';
                                    break;
                                case 'regularizada':
                                    $estado_class = 'status status-info';
                                    $estado_text = 'Regularizada';
                                    break;
                                case 'aprobada':
                                    $estado_class = 'status status-success';
                                    $estado_text = 'Aprobada';
                                    break;
                                default:
                                    $estado_class = 'status';
                                    $estado_text = ucfirst($p['estado']);
                            }
                            ?>
                            <span class="<?php echo $estado_class; ?>"><?php echo $estado_text; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($p['observaciones'] ?? ''); ?></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <?php if ($p['estado'] !== 'aprobada'): ?>
                                <form method="POST" onsubmit="return confirm('¿Aprobar materia previa? Se registrará nota 7 en todos los cuatrimestres del boletín.');" style="margin: 0;">
                                    <input type="hidden" name="previa_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="aprobar_previa" class="btn btn-success btn-sm" title="Aprobar con nota 7">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('¿Eliminar registro?');" style="margin: 0;">
                                    <input type="hidden" name="previa_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="eliminar_previa" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Eliminar
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

<?php include 'includes/footer.php'; ?> 