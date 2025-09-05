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

$pageTitle = 'Estudiantes (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';

include 'includes/header.php';

use SistemaAdmin\Controllers\EstudianteController;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Services\ServicioEstudiantes;

// Inicializar la nueva arquitectura
$db = Database::getInstance();
$estudianteMapper = new EstudianteMapper($db);
$servicioEstudiantes = new ServicioEstudiantes($estudianteMapper);
$estudianteController = new EstudianteController($servicioEstudiantes);

$action = $_GET['action'] ?? '';
$success_message = '';
$error_message = '';

// Procesar formulario de nuevo estudiante usando la nueva arquitectura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_estudiante'])) {
    try {
        $data = [
            'dni' => $_POST['dni'],
            'apellido' => $_POST['apellido'],
            'nombre' => $_POST['nombre'],
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?: null,
            'grupo_sanguineo' => $_POST['grupo_sanguineo'] ?: null,
            'obra_social' => $_POST['obra_social'] ?: null,
            'domicilio' => $_POST['domicilio'] ?: null,
            'telefono_fijo' => $_POST['telefono_fijo'] ?: null,
            'telefono_celular' => $_POST['telefono_celular'] ?: null,
            'email' => $_POST['email'] ?: null,
            'curso_id' => $_POST['curso_id'] ?: null
        ];
        
        $resultado = $estudianteController->crear($data);
        
        if ($resultado['success']) {
            $success_message = "Estudiante registrado correctamente";
            $action = ''; // Limpiar acción para ocultar formulario
        } else {
            $error_message = $resultado['error'] ?? 'Error al registrar estudiante';
        }
    } catch (Exception $e) {
        $error_message = "Error al registrar estudiante: " . $e->getMessage();
    }
}

// Filtros
$curso_filter = $_GET['curso'] ?? '';
$search = $_GET['search'] ?? '';

// Obtener estudiantes usando la nueva arquitectura
if ($search) {
    $resultado = $estudianteController->buscar($search);
    $estudiantes_data = $resultado['success'] ? $resultado['data'] : [];
} else {
    $resultado = $estudianteController->listar();
    $estudiantes_data = $resultado['success'] ? $resultado['data'] : [];
}

// Filtrar por curso si se especifica
if ($curso_filter && !empty($estudiantes_data)) {
    $estudiantes_data = array_filter($estudiantes_data, function($estudiante) use ($curso_filter) {
        return $estudiante['curso_id'] == $curso_filter;
    });
}

// Obtener cursos para formulario y filtros (usando la base de datos directamente por ahora)

$cursos = $db->fetchAll("
    SELECT c.id, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno
    FROM cursos c
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    LEFT JOIN turnos t ON c.turno_id = t.id
    WHERE c.activo = 1
    ORDER BY c.anio, c.division
");

// Estadísticas usando la nueva arquitectura
$estadisticas = $estudianteController->estadisticas();
$total_estudiantes = $estadisticas['success'] ? ($estadisticas['data']['total_estudiantes'] ?? count($estudiantes_data)) : count($estudiantes_data);
$estudiantes_sin_curso = $estadisticas['success'] ? ($estadisticas['data']['sin_contacto'] ?? 0) : 0;
?>

<section class="estudiantes-section">
    <div class="section-header">
        <h2>Gestión de Estudiantes</h2>
        <a href="estudiantes.php?action=nuevo" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Estudiante
        </a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_estudiantes); ?></h3>
                <p>Total Estudiantes</p>
            </div>
        </div>
        
        <?php if ($estudiantes_sin_curso > 0): ?>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($estudiantes_sin_curso); ?></h3>
                <p>Sin Cursos Asignados</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Formulario nuevo estudiante -->
    <?php if ($action === 'nuevo'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Registrar Nuevo Estudiante</h3>
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
                    <label for="grupo_sanguineo">Grupo Sanguíneo:</label>
                    <select name="grupo_sanguineo" id="grupo_sanguineo">
                        <option value="">Seleccionar</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="obra_social">Obra Social:</label>
                    <input type="text" name="obra_social" id="obra_social" maxlength="100">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefono_fijo">Teléfono Fijo:</label>
                    <input type="tel" name="telefono_fijo" id="telefono_fijo" maxlength="20">
                </div>
                
                <div class="form-group">
                    <label for="telefono_celular">Teléfono Celular:</label>
                    <input type="tel" name="telefono_celular" id="telefono_celular" maxlength="20">
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" maxlength="100">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="domicilio">Domicilio:</label>
                    <textarea name="domicilio" id="domicilio" placeholder="Dirección completa"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="curso_id">Curso:</label>
                    <select name="curso_id" id="curso_id">
                        <option value="">Asignar después</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>">
                            <?php echo $curso['anio'] . '° ' . $curso['division'] . ' - ' . $curso['especialidad'] . ' (' . $curso['turno'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="guardar_estudiante" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Estudiante
                </button>
                <a href="estudiantes.php" class="btn btn-secondary">
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
                    <label for="curso">Curso:</label>
                    <select name="curso" id="curso">
                        <option value="">Todos los cursos</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>" 
                                <?php echo $curso_filter == $curso['id'] ? 'selected' : ''; ?>>
                            <?php echo $curso['anio'] . '° ' . $curso['division'] . ' - ' . $curso['especialidad']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="estudiantes.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de estudiantes -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Estudiantes Registrados (<?php echo number_format($total_estudiantes); ?>)</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Apellido y Nombre</th>
                        <th>Curso</th>
                        <th>Turno</th>
                        <th>Contacto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($estudiantes_data)): ?>
                        <?php foreach ($estudiantes_data as $estudiante): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($estudiante['dni']); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']); ?></strong>
                                <?php if ($estudiante['fecha_nacimiento']): ?>
                                <br><small>
                                    <i class="fas fa-birthday-cake"></i>
                                    <?php echo date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])); ?>
                                    (<?php echo $estudiante['edad']; ?> años)
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($estudiante['curso_id']): ?>
                                    <span class="status status-success">
                                        Curso ID: <?php echo $estudiante['curso_id']; ?>
                                    </span>
                                    <br><small>Información del curso</small>
                                <?php else: ?>
                                    <span class="status status-warning">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status status-warning">-</span>
                            </td>
                            <td>
                                <?php if ($estudiante['telefono_celular']): ?>
                                    <i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars($estudiante['telefono_celular']); ?>
                                <?php elseif ($estudiante['telefono_fijo']): ?>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($estudiante['telefono_fijo']); ?>
                                <?php else: ?>
                                    <span class="status status-warning">Sin teléfono</span>
                                <?php endif; ?>
                                <?php if ($estudiante['email']): ?>
                                    <br><small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($estudiante['email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="estudiante_ficha.php?id=<?php echo $estudiante['id']; ?>" 
                                   class="btn btn-sm btn-primary" title="Ver ficha completa">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="llamados.php?estudiante=<?php echo $estudiante['id']; ?>" 
                                   class="btn btn-sm btn-danger" title="Ver llamados">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 2rem; color: var(--secondary-color);">
                                <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <br>No se encontraron estudiantes con los criterios especificados
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
