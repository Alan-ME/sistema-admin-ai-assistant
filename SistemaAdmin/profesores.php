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

// Endpoint AJAX para obtener profesores por materia y curso
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_profesores_por_materia') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $db = Database::getInstance();
        $materia_id = $_GET['materia_id'] ?? null;
        $curso_id = $_GET['curso_id'] ?? null;
        
        if (!$materia_id) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de materia requerido'
            ]);
            exit;
        }
        
        // Obtener profesores que dictan la materia específica Y están asignados al curso
        // (Ahora permite múltiples profesores por curso, pero no dos con la misma materia)
        $profesores = $db->fetchAll("
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
        ", [$materia_id, $curso_id]);
        
        echo json_encode([
            'success' => true,
            'profesores' => $profesores
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener profesores: ' . $e->getMessage()
        ]);
        exit;
    }
}

$pageTitle = 'Profesores (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';
include 'includes/header.php';

use SistemaAdmin\Controllers\ProfesorController;
use SistemaAdmin\Mappers\ProfesorMapper;
use SistemaAdmin\Services\ServicioProfesores;

// Inicializar la nueva arquitectura
$db = Database::getInstance();
$profesorMapper = new ProfesorMapper($db);
$servicioProfesores = new ServicioProfesores($profesorMapper);
$profesorController = new ProfesorController($servicioProfesores);

$action = $_GET['action'] ?? '';
$success_message = '';
$error_message = '';

// Procesar formulario de nuevo profesor usando la nueva arquitectura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_profesor'])) {
    try {
        $data = [
            'dni' => $_POST['dni'],
            'apellido' => $_POST['apellido'],
            'nombre' => $_POST['nombre'],
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?: null,
            'domicilio' => $_POST['domicilio'] ?: null,
            'telefono_fijo' => $_POST['telefono_fijo'] ?: null,
            'telefono_celular' => $_POST['telefono_celular'] ?: null,
            'email' => $_POST['email'] ?: null,
            'titulo' => $_POST['titulo'] ?: null,
            'especialidad' => $_POST['especialidad'] ?: null,
            'fecha_ingreso' => $_POST['fecha_ingreso'] ?: null
        ];
        
        $resultado = $profesorController->crear($data);
        
        if ($resultado['success']) {
            $success_message = "Profesor registrado correctamente";
            $action = ''; // Limpiar acción para ocultar formulario
        } else {
            $error_message = $resultado['error'] ?? 'Error al registrar profesor';
        }
    } catch (Exception $e) {
        $error_message = "Error al registrar profesor: " . $e->getMessage();
    }
}

// Procesar eliminación de profesor usando la nueva arquitectura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_profesor'])) {
    try {
        $profesor_id = $_POST['profesor_id'];
        
        $resultado = $profesorController->eliminar($profesor_id);
        
        if ($resultado['success']) {
            $success_message = "Profesor eliminado correctamente";
        } else {
            $error_message = $resultado['error'] ?? 'Error al eliminar profesor';
        }
    } catch (Exception $e) {
        $error_message = "Error al eliminar profesor: " . $e->getMessage();
    }
}

// Filtros
$especialidad_filter = $_GET['especialidad'] ?? '';
$search = $_GET['search'] ?? '';
$curso_filter = $_GET['curso'] ?? '';

// Obtener profesores usando la nueva arquitectura
if ($search) {
    $resultado = $profesorController->buscar($search);
    $profesores_data = $resultado['success'] ? $resultado['data'] : [];
} else {
    $resultado = $profesorController->listar();
    $profesores_data = $resultado['success'] ? $resultado['data'] : [];
}

// Filtrar por especialidad si se especifica
if ($especialidad_filter && !empty($profesores_data)) {
    $profesores_data = array_filter($profesores_data, function($profesor) use ($especialidad_filter) {
        return strpos($profesor['especialidad'] ?? '', $especialidad_filter) !== false;
    });
}

// Obtener especialidades únicas para filtros (usando la base de datos directamente por ahora)
$especialidades = $db->fetchAll("
    SELECT DISTINCT especialidad 
    FROM profesores 
    WHERE activo = 1 AND especialidad IS NOT NULL AND especialidad != ''
    ORDER BY especialidad
");

// Estadísticas usando la nueva arquitectura
$estadisticas = $profesorController->estadisticas();
$total_profesores = $estadisticas['success'] ? ($estadisticas['data']['total_profesores'] ?? count($profesores_data)) : count($profesores_data);
$profesores_sin_cursos = $estadisticas['success'] ? ($estadisticas['data']['sin_cursos'] ?? 0) : 0;
?>

<section class="profesores-section">
    <div class="section-header">
        <h2>
            Gestión de Profesores
            <?php if ($curso_filter): ?>
                <?php 
                $curso_info = $db->fetch("
                    SELECT c.anio, c.division, esp.nombre as especialidad 
                    FROM cursos c 
                    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id 
                    WHERE c.id = ?
                ", [$curso_filter]);
                if ($curso_info): ?>
                    <span style="font-size: 0.8em; color: var(--secondary-color); font-weight: normal;">
                        - Profesores de <?php echo $curso_info['anio'] . '° ' . $curso_info['division']; ?>
                        <?php if ($curso_info['especialidad']): ?>
                            (<?php echo htmlspecialchars($curso_info['especialidad']); ?>)
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </h2>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <?php if ($curso_filter): ?>
                <a href="cursos.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Cursos
                </a>
            <?php endif; ?>
            <a href="profesores.php?action=nuevo" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Profesor
            </a>
        </div>
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



    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_profesores); ?></h3>
                <p>Total Profesores</p>
            </div>
        </div>
        
        <?php if ($profesores_sin_cursos > 0): ?>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($profesores_sin_cursos); ?></h3>
                <p>Sin Cursos Asignados</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Formulario nuevo profesor -->
    <?php if ($action === 'nuevo'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Registrar Nuevo Profesor</h3>
        </div>
        <form method="POST" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="dni">DNI: *</label>
                    <input type="text" name="dni" id="dni" required maxlength="20" 
                           placeholder="Ej: 12345678">
                </div>
                
                <div class="form-group">
                    <label for="apellido">Apellido: *</label>
                    <input type="text" name="apellido" id="apellido" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="nombre">Nombre: *</label>
                    <input type="text" name="nombre" id="nombre" required maxlength="100">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento">
                </div>
                
                <div class="form-group">
                    <label for="fecha_ingreso">Fecha de Ingreso:</label>
                    <input type="date" name="fecha_ingreso" id="fecha_ingreso">
                </div>
                
                <div class="form-group">
                    <label for="titulo">Título:</label>
                    <input type="text" name="titulo" id="titulo" maxlength="200" 
                           placeholder="Ej: Profesor de Matemática">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="especialidad">Especialidad:</label>
                    <input type="text" name="especialidad" id="especialidad" maxlength="200" 
                           placeholder="Ej: Matemática, Física, etc.">
                </div>
                
                <div class="form-group">
                    <label for="telefono_fijo">Teléfono Fijo:</label>
                    <input type="tel" name="telefono_fijo" id="telefono_fijo" maxlength="20">
                </div>
                
                <div class="form-group">
                    <label for="telefono_celular">Teléfono Celular:</label>
                    <input type="tel" name="telefono_celular" id="telefono_celular" maxlength="20">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="domicilio">Domicilio:</label>
                    <textarea name="domicilio" id="domicilio" placeholder="Dirección completa"></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="guardar_profesor" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Profesor
                </button>
                <a href="profesores.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
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
                    <label for="search">Buscar:</label>
                    <input type="text" name="search" id="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Apellido, nombre o DNI">
                </div>
                
                <div class="form-group">
                    <label for="especialidad">Especialidad:</label>
                    <select name="especialidad" id="especialidad">
                        <option value="">Todas las especialidades</option>
                        <?php foreach ($especialidades as $esp): ?>
                        <option value="<?php echo htmlspecialchars($esp['especialidad']); ?>" 
                                <?php echo $especialidad_filter == $esp['especialidad'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($esp['especialidad']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="profesores.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de profesores -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Profesores Registrados (<?php echo number_format($total_profesores); ?>)</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Apellido y Nombre</th>
                        <th>Especialidad</th>
                        <th>Cursos Asignados</th>
                        <th>Contacto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($profesores_data)): ?>
                        <?php foreach ($profesores_data as $profesor): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($profesor['dni']); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($profesor['apellido'] . ', ' . $profesor['nombre']); ?></strong>
                                <?php if ($profesor['fecha_nacimiento']): ?>
                                <br><small>
                                    <i class="fas fa-birthday-cake"></i>
                                    <?php echo date('d/m/Y', strtotime($profesor['fecha_nacimiento'])); ?>
                                    (<?php echo $profesor['edad']; ?> años)
                                </small>
                                <?php endif; ?>
                                <?php if ($profesor['titulo']): ?>
                                <br><small><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($profesor['titulo']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($profesor['especialidad']): ?>
                                    <span class="status status-success">
                                        <?php echo htmlspecialchars($profesor['especialidad']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status status-warning">Sin especificar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status status-warning">Información de cursos</span>
                                <br><small>Por implementar</small>
                            </td>
                            <td>
                                <?php if ($profesor['telefono_celular']): ?>
                                    <i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars($profesor['telefono_celular']); ?>
                                <?php elseif ($profesor['telefono_fijo']): ?>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($profesor['telefono_fijo']); ?>
                                <?php else: ?>
                                    <span class="status status-warning">Sin teléfono</span>
                                <?php endif; ?>
                                <?php if ($profesor['email']): ?>
                                    <br><small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profesor['email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="profesor_ficha.php?id=<?php echo $profesor['id']; ?>" 
                                   class="btn btn-sm btn-primary" title="Ver ficha completa">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <?php if (hasRole('admin') || hasRole('directivo')): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de que desea eliminar este profesor?')">
                                    <input type="hidden" name="profesor_id" value="<?php echo $profesor['id']; ?>">
                                    <button type="submit" name="eliminar_profesor" class="btn btn-sm btn-danger" title="Eliminar profesor">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 2rem; color: var(--secondary-color);">
                                <i class="fas fa-chalkboard-teacher" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <br>No se encontraron profesores con los criterios especificados
                                <br><small>Prueba modificando los filtros de búsqueda</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
