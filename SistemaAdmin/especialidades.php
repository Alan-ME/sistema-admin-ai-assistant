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

$pageTitle = 'Especialidades (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';
include 'includes/header.php';

// Verificar permisos (solo admin y directivo) - después del header para tener acceso a hasRole()
if (!(hasRole('admin') || hasRole('directivo'))) {
    header('Location: index.php?error=unauthorized');
    exit();
}

$action = $_GET['action'] ?? '';
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_especialidad'])) {
    try {
        $db->query("INSERT INTO especialidades (nombre, descripcion, activa) VALUES (?, ?, 1)", [
            $_POST['nombre'],
            $_POST['descripcion'] ?: null
        ]);
        $success_message = 'Especialidad creada';
        $action = '';
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desactivar_especialidad'])) {
    try {
        $db->query("UPDATE especialidades SET activa = 0 WHERE id = ?", [$_POST['especialidad_id']]);
        $success_message = 'Especialidad desactivada';
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
    }
}

$especialidades = $db->fetchAll("SELECT * FROM especialidades WHERE activa = 1 ORDER BY nombre");
?>

<section class="especialidades-section">
    <div class="section-header">
        <h2>Especialidades</h2>
        <a href="especialidades.php?action=nueva" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Especialidad</a>
    </div>

    <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-error"><?php echo $error_message; ?></div><?php endif; ?>

    <?php if ($action === 'nueva'): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Crear Especialidad</h3></div>
        <form method="POST" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <input type="text" id="descripcion" name="descripcion">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="guardar_especialidad" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                <a href="especialidades.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Listado</h3></div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($especialidades as $e): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($e['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($e['descripcion'] ?? ''); ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('¿Desactivar esta especialidad?');">
                                <input type="hidden" name="especialidad_id" value="<?php echo $e['id']; ?>">
                                <button class="btn btn-danger btn-sm" name="desactivar_especialidad"><i class="fas fa-trash"></i> Desactivar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 