<?php 
// Iniciar sesión al principio
session_start();

// Incluir la nueva arquitectura
require_once 'src/autoload.php';
require_once 'config/database.php';
require_once 'includes/character_encoding.php';

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

$pageTitle = 'Ficha del Profesor (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';
include 'includes/header.php';

// Endpoint AJAX para obtener materias por curso
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_materias_curso') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $curso_id = $_GET['curso_id'] ?? '';
        
        if (empty($curso_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'ID de curso requerido'
            ]);
            exit;
        }
        
        // Obtener las materias asignadas a este curso
        $materias = $db->fetchAll("
            SELECT DISTINCT m.id, m.nombre
            FROM materias m
            INNER JOIN materia_curso mc ON m.id = mc.materia_id
            WHERE mc.curso_id = ? AND m.activa = 1
            ORDER BY m.nombre
        ", [$curso_id]);
        
        echo json_encode([
            'success' => true,
            'materias' => $materias
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener materias: ' . $e->getMessage()
        ]);
        exit;
    }
}

$profesor_id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';
$success_message = '';
$error_message = '';

if (!$profesor_id) {
    header('Location: profesores.php');
    exit();
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_profesor'])) {
    try {
        $db->query("
            UPDATE profesores SET 
                telefono_fijo = ?, telefono_celular = ?, email = ?, domicilio = ?,
                titulo = ?, especialidad = ?
            WHERE id = ?
        ", [
            $_POST['telefono_fijo'] ?: null,
            $_POST['telefono_celular'] ?: null,
            $_POST['email'] ?: null,
            $_POST['domicilio'] ?: null,
            $_POST['titulo'] ?: null,
            $_POST['especialidad'] ?: null,
            $profesor_id
        ]);
        
        $success_message = "Información del profesor actualizada correctamente";
        $action = ''; // Volver a vista normal
    } catch (Exception $e) {
        $error_message = "Error al actualizar profesor: " . $e->getMessage();
    }
}

// Procesar asignación de curso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_curso'])) {
    try {
        $curso_id = $_POST['curso_id'];
        $anio_academico = $_POST['anio_academico'];
        
        // Verificar si ya existe la asignación para este profesor
        $asignacion_existente = $db->fetch("
            SELECT id FROM profesor_curso 
            WHERE profesor_id = ? AND curso_id = ? AND anio_academico = ?
        ", [$profesor_id, $curso_id, $anio_academico]);
        
        if ($asignacion_existente) {
            // Reactivar asignación existente
            $db->query("
                UPDATE profesor_curso SET activo = 1 
                WHERE profesor_id = ? AND curso_id = ? AND anio_academico = ?
            ", [$profesor_id, $curso_id, $anio_academico]);
        } else {
            // Crear nueva asignación
            $db->query("
                INSERT INTO profesor_curso (profesor_id, curso_id, anio_academico)
                VALUES (?, ?, ?)
            ", [$profesor_id, $curso_id, $anio_academico]);
        }
        
        $success_message = "Curso asignado correctamente";
    } catch (Exception $e) {
        $error_message = "Error al asignar curso: " . $e->getMessage();
    }
}

// Procesar desasignación de curso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desasignar_curso'])) {
    try {
        $db->query("
            UPDATE profesor_curso SET activo = 0 
            WHERE id = ? AND profesor_id = ?
        ", [$_POST['asignacion_id'], $profesor_id]);
        
        $success_message = "Curso desasignado correctamente";
    } catch (Exception $e) {
        $error_message = "Error al desasignar curso: " . $e->getMessage();
    }
}

// Procesar creación de suplencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_suplencia'])) {
    try {
        $materia_id = $_POST['materia_id'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?: null;
        $motivo = $_POST['motivo'] ?? '';
        $suplente_id = $_POST['suplente_id'] ?: null;
        $fuera_servicio = isset($_POST['fuera_servicio']) ? 1 : 0;
        
        // Validar que los campos requeridos no estén vacíos
        if (empty($materia_id) || empty($fecha_inicio) || empty($motivo)) {
            $error_message = "Debe completar todos los campos requeridos (materia, fecha de inicio y motivo)";
        } else {
            // Verificar si ya existe una suplencia activa para esta materia
            $suplencia_existente = $db->fetch("
                SELECT id FROM suplencias 
                WHERE profesor_id = ? AND materia_id = ? AND estado = 'activa'
            ", [$profesor_id, $materia_id]);
            
            if ($suplencia_existente) {
                $error_message = "Ya existe una suplencia activa para esta materia";
            } else {
                $db->query("
                    INSERT INTO suplencias (profesor_id, suplente_id, materia_id, 
                                           fecha_inicio, fecha_fin, motivo, fuera_servicio, usuario_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [$profesor_id, $suplente_id, $materia_id, 
                     $fecha_inicio, $fecha_fin, $motivo, $fuera_servicio, $_SESSION['usuario_id']]);
                
                $success_message = "Suplencia creada correctamente";
            }
        }
    } catch (Exception $e) {
        $error_message = "Error al crear suplencia: " . $e->getMessage();
    }
}

// Procesar finalización de suplencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_suplencia'])) {
    try {
        $suplencia_id = $_POST['suplencia_id'] ?? '';
        
        $db->query("
            UPDATE suplencias SET estado = 'finalizada', fecha_fin = CURDATE()
            WHERE id = ? AND profesor_id = ?
        ", [$suplencia_id, $profesor_id]);
        
        $success_message = "Suplencia finalizada correctamente";
    } catch (Exception $e) {
        $error_message = "Error al finalizar suplencia: " . $e->getMessage();
    }
}

// Procesar asignación de materias al profesor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_materia'])) {
    try {
        $materia_id = $_POST['materia_id'] ?? '';
        $curso_id = $_POST['curso_id'] ?? '';
        
        if (empty($materia_id) || empty($curso_id)) {
            $error_message = "Debe seleccionar tanto la materia como el curso";
        } else {
            // Verificar si ya está asignada esta materia para este curso específico
            $materia_existente = $db->fetch("
                SELECT pm.id FROM profesor_materia pm
                WHERE pm.profesor_id = ? AND pm.materia_id = ? AND pm.curso_id = ? AND pm.activo = 1
            ", [$profesor_id, $materia_id, $curso_id]);
            
            if ($materia_existente) {
                $error_message = "Esta materia ya está asignada para este curso";
            } else {
                // Verificar si hay conflicto: otro profesor con el mismo curso y materia
                $conflicto = $db->fetch("
                    SELECT p.apellido, p.nombre, c.anio, c.division
                    FROM profesor_materia pm
                    JOIN profesores p ON pm.profesor_id = p.id
                    JOIN cursos c ON pm.curso_id = c.id
                    WHERE pm.materia_id = ? 
                    AND pm.curso_id = ?
                    AND pm.profesor_id != ?
                    AND pm.activo = 1
                    AND pm.anio_academico = YEAR(CURDATE())
                ", [$materia_id, $curso_id, $profesor_id]);
                
                if ($conflicto) {
                    $error_message = "No se puede asignar esta materia. El profesor " . $conflicto['apellido'] . ", " . $conflicto['nombre'] . " ya dicta esta materia en este curso";
                } else {
                    $db->query("
                        INSERT INTO profesor_materia (profesor_id, materia_id, curso_id, activo, fecha_asignacion, anio_academico)
                        VALUES (?, ?, ?, 1, CURDATE(), YEAR(CURDATE()))
                    ", [$profesor_id, $materia_id, $curso_id]);
                    
                    $success_message = "Materia asignada correctamente al curso especificado";
                }
            }
        }
    } catch (Exception $e) {
        $error_message = "Error al asignar materia: " . $e->getMessage();
    }
}

// Procesar desasignación de materias
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desasignar_materia'])) {
    try {
        $materia_curso_id = $_POST['materia_curso_id'] ?? '';
        
        $db->query("
            UPDATE profesor_materia SET activo = 0
            WHERE id = ? AND profesor_id = ?
        ", [$materia_curso_id, $profesor_id]);
        
        $success_message = "Materia desasignada correctamente del curso especificado";
    } catch (Exception $e) {
        $error_message = "Error al desasignar materia: " . $e->getMessage();
    }
}

// Procesar nuevo suplente específico para el profesor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_suplente'])) {
    try {
        // Validar campos requeridos
        if (empty($_POST['dni'] ?? '') || empty($_POST['apellido'] ?? '') || empty($_POST['nombre'] ?? '')) {
            $error_message = "Debe completar DNI, Apellido y Nombre del suplente";
        } else {
        $db->query("
            INSERT INTO suplentes (dni, apellido, nombre, telefono_celular, email, especialidad)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $_POST['dni'] ?? '',
            $_POST['apellido'] ?? '',
            $_POST['nombre'] ?? '',
            $_POST['telefono_celular'] ?: null,
            $_POST['email'] ?: null,
            $_POST['especialidad'] ?: null
        ]);
        
        $nuevo_suplente_id = $db->lastInsertId();
        
        // Si se está creando desde el formulario de suplencia, asignar automáticamente
        if (isset($_POST['asignar_automaticamente']) && $_POST['asignar_automaticamente'] == '1') {
            $_POST['suplente_id'] = $nuevo_suplente_id;
        }
        
        $success_message = "Suplente registrado correctamente";
        }
    } catch (Exception $e) {
        $error_message = "Error al registrar suplente: " . $e->getMessage();
    }
}

// Obtener datos del profesor
$profesor = $db->fetch("
    SELECT * FROM profesores WHERE id = ? AND activo = 1
", [$profesor_id]);

if (!$profesor) {
    header('Location: profesores.php?error=not_found');
    exit();
}

// Obtener cursos asignados
$cursos_asignados = $db->fetchAll("
    SELECT pc.*, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno
    FROM profesor_curso pc
    JOIN cursos c ON pc.curso_id = c.id
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    LEFT JOIN turnos t ON c.turno_id = t.id
    WHERE pc.profesor_id = ? AND pc.activo = 1
    ORDER BY pc.anio_academico DESC, c.anio, c.division
", [$profesor_id]);

// Las materias asignadas se obtienen más abajo con información de cursos

// Obtener cursos disponibles para asignar
$cursos_disponibles = $db->fetchAll("
    SELECT c.id, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno
    FROM cursos c
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    LEFT JOIN turnos t ON c.turno_id = t.id
    WHERE c.activo = 1
    ORDER BY c.anio, c.division
");

// Obtener materias disponibles
$materias_disponibles = $db->fetchAll("
    SELECT id, nombre FROM materias 
    WHERE activa = 1 
    ORDER BY nombre
");

// Calcular años de antigüedad
$anios_antiguedad = $profesor['fecha_ingreso'] ? 
    floor((time() - strtotime($profesor['fecha_ingreso'])) / (365.25 * 24 * 3600)) : 0;

// Obtener suplencias activas del profesor
$suplencias_activas = $db->fetchAll("
    SELECT s.*, m.nombre as materia,
           sup.apellido as suplente_apellido, sup.nombre as suplente_nombre
    FROM suplencias s
    JOIN materias m ON s.materia_id = m.id
    LEFT JOIN suplentes sup ON s.suplente_id = sup.id
    WHERE s.profesor_id = ? AND s.estado = 'activa'
    ORDER BY s.fecha_inicio DESC
", [$profesor_id]);

// Obtener suplentes disponibles (se filtrarán por materia en JavaScript)
$suplentes_disponibles = $db->fetchAll("
    SELECT id, apellido, nombre, especialidad 
    FROM suplentes 
    WHERE activo = 1
    ORDER BY apellido, nombre
");

// Obtener materias asignadas al profesor por curso específico
$materias_asignadas = $db->fetchAll("
    SELECT pm.id, pm.materia_id, pm.curso_id, m.nombre, pm.fecha_asignacion, pm.anio_academico,
           CASE 
               WHEN pm.curso_id IS NOT NULL THEN 
                   CONCAT(c.anio, '° ', c.division, 
                       CASE WHEN c.anio > 3 THEN CONCAT(' - ', esp.nombre) ELSE '' END)
               ELSE 'Sin curso específico'
           END as curso
    FROM profesor_materia pm
    JOIN materias m ON pm.materia_id = m.id
    LEFT JOIN cursos c ON pm.curso_id = c.id AND c.activo = 1
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    WHERE pm.profesor_id = ? AND pm.activo = 1 AND m.activa = 1
    ORDER BY pm.anio_academico DESC, COALESCE(c.anio, 0), COALESCE(c.division, ''), m.nombre
", [$profesor_id]);

// Obtener todas las materias disponibles (se filtrarán en JavaScript según el curso seleccionado)
$materias_disponibles = $db->fetchAll("
    SELECT m.id, m.nombre
    FROM materias m
    WHERE m.activa = 1 
    ORDER BY m.nombre
");

// Obtener materias que dicta el profesor (para suplencias) - usar las asignadas
$materias_profesor = $materias_asignadas;

// Calcular estadísticas (después de todas las operaciones POST)
$total_cursos_actuales = $db->fetch("
    SELECT COUNT(*) as total FROM profesor_curso 
    WHERE profesor_id = ? AND anio_academico = YEAR(CURDATE()) AND activo = 1
", [$profesor_id])['total'];

$total_materias_actuales = $db->fetch("
    SELECT COUNT(*) as total FROM profesor_materia 
    WHERE profesor_id = ? AND activo = 1
", [$profesor_id])['total'];
?>

<section class="profesor-ficha-section">
    <div class="section-header">
        <h2>Ficha del Profesor</h2>
        <div class="header-actions">
            <a href="profesores.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Profesores
            </a>
            <?php if (hasRole('admin') || hasRole('directivo')): ?>
            <a href="profesor_ficha.php?id=<?php echo $profesor_id; ?>&action=editar" class="btn btn-primary">
                <i class="fas fa-edit"></i> Editar Información
            </a>
            <?php endif; ?>
        </div>
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

    <!-- Información personal -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Información Personal</h3>
        </div>
        <div class="card-body">
            <div class="student-profile">
                <div class="profile-photo">
                    <div class="default-photo">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($profesor['apellido'] . ', ' . $profesor['nombre']); ?></h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>DNI:</strong>
                            <span><?php echo htmlspecialchars($profesor['dni']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Fecha de Nacimiento:</strong>
                            <span>
                                <?php if ($profesor['fecha_nacimiento']): ?>
                                    <?php echo date('d/m/Y', strtotime($profesor['fecha_nacimiento'])); ?>
                                    (<?php echo floor((time() - strtotime($profesor['fecha_nacimiento'])) / (365.25 * 24 * 3600)); ?> años)
                                <?php else: ?>
                                    No registrada
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Título:</strong>
                            <span><?php echo htmlspecialchars($profesor['titulo']) ?: 'No registrado'; ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Especialidad:</strong>
                            <span>
                                <?php if ($profesor['especialidad']): ?>
                                    <span class="status status-success"><?php echo htmlspecialchars($profesor['especialidad']); ?></span>
                                <?php else: ?>
                                    <span class="status status-warning">No especificada</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Fecha de Ingreso:</strong>
                            <span>
                                <?php if ($profesor['fecha_ingreso']): ?>
                                    <?php echo date('d/m/Y', strtotime($profesor['fecha_ingreso'])); ?>
                                    (<?php echo $anios_antiguedad; ?> años de antigüedad)
                                <?php else: ?>
                                    No registrada
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-info">
                <h4>Información de Contacto</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Domicilio:</strong>
                        <span><?php echo htmlspecialchars($profesor['domicilio']) ?: 'No registrado'; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Teléfono Fijo:</strong>
                        <span><?php echo htmlspecialchars($profesor['telefono_fijo']) ?: 'No registrado'; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Teléfono Celular:</strong>
                        <span><?php echo htmlspecialchars($profesor['telefono_celular']) ?: 'No registrado'; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Email:</strong>
                        <span><?php echo htmlspecialchars($profesor['email']) ?: 'No registrado'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-chalkboard"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_cursos_actuales; ?></h3>
                <p>Cursos Actuales</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon secondary">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_materias_actuales; ?></h3>
                <p>Materias Actuales</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $anios_antiguedad; ?></h3>
                <p>Años de Antigüedad</p>
            </div>
        </div>
    </div>

    <!-- Formulario de edición -->
    <?php if ($action === 'editar' && (hasRole('admin') || hasRole('directivo'))): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Editar Información del Profesor</h3>
        </div>
        <form method="POST" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="telefono_fijo">Teléfono Fijo:</label>
                    <input type="tel" name="telefono_fijo" id="telefono_fijo" 
                           value="<?php echo htmlspecialchars($profesor['telefono_fijo'] ?? ''); ?>" maxlength="20">
                </div>
                
                <div class="form-group">
                    <label for="telefono_celular">Teléfono Celular:</label>
                    <input type="tel" name="telefono_celular" id="telefono_celular" 
                           value="<?php echo htmlspecialchars($profesor['telefono_celular'] ?? ''); ?>" maxlength="20">
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" 
                           value="<?php echo htmlspecialchars($profesor['email'] ?? ''); ?>" maxlength="100">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="titulo">Título:</label>
                    <input type="text" name="titulo" id="titulo" 
                           value="<?php echo htmlspecialchars($profesor['titulo'] ?? ''); ?>" maxlength="200">
                </div>
                
                <div class="form-group">
                    <label for="especialidad">Especialidad:</label>
                    <input type="text" name="especialidad" id="especialidad" 
                           value="<?php echo htmlspecialchars($profesor['especialidad'] ?? ''); ?>" maxlength="200">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="domicilio">Domicilio:</label>
                    <textarea name="domicilio" id="domicilio" placeholder="Dirección completa"><?php echo htmlspecialchars($profesor['domicilio'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="actualizar_profesor" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="profesor_ficha.php?id=<?php echo $profesor_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Cursos asignados -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chalkboard"></i>
                Cursos Asignados (<?php echo count($cursos_asignados); ?>)
            </h3>
            <?php if (hasRole('admin') || hasRole('directivo')): ?>
            <button class="btn btn-primary btn-sm" onclick="toggleForm('asignar-curso')" style="margin-left: 1rem;">
                <i class="fas fa-plus"></i> Asignar Curso
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Formulario para asignar curso -->
        <?php if (hasRole('admin') || hasRole('directivo')): ?>
        <div id="asignar-curso" class="form-section" style="display: none;">
            <form method="POST" class="form-container">
                <div class="form-row">
                    <div class="form-group">
                        <label for="curso_id">Curso:</label>
                        <select name="curso_id" id="curso_id" required>
                            <option value="">Seleccionar curso</option>
                            <?php foreach ($cursos_disponibles as $curso): ?>
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
                        <label for="anio_academico">Año Académico:</label>
                        <select name="anio_academico" id="anio_academico" required>
                            <option value="">Seleccionar año</option>
                            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == date('Y') ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="asignar_curso" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Asignar Curso
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('asignar-curso')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="table-container">
            <?php if (!empty($cursos_asignados)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Año Académico</th>
                        <th>Curso</th>
                        <th>Especialidad</th>
                        <th>Turno</th>
                        <th>Fecha Asignación</th>
                        <?php if (hasRole('admin') || hasRole('directivo')): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursos_asignados as $curso): ?>
                    <tr>
                        <td>
                            <span class="status status-info"><?php echo $curso['anio_academico']; ?></span>
                        </td>
                        <td>
                            <strong><?php echo $curso['anio'] . '° ' . $curso['division']; ?></strong>
                        </td>
                        <td>
                            <?php 
                            if ($curso['anio'] <= 3) {
                                echo '<span class="text-muted">Sin especialidad</span>';
                            } else {
                                echo htmlspecialchars($curso['especialidad']);
                            }
                            ?>
                        </td>
                        <td>
                            <i class="fas fa-clock"></i> <?php echo htmlspecialchars($curso['turno']); ?>
                        </td>
                        <td>
                            <small><?php echo date('d/m/Y', strtotime($curso['fecha_asignacion'])); ?></small>
                        </td>
                        <?php if (hasRole('admin') || hasRole('directivo')): ?>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de que desea desasignar este curso?')">
                                <input type="hidden" name="asignacion_id" value="<?php echo $curso['id']; ?>">
                                <button type="submit" name="desasignar_curso" class="btn btn-sm btn-danger" title="Desasignar curso">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="text-center" style="padding: 2rem; color: var(--secondary-color);">
                <i class="fas fa-chalkboard" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <br>No hay cursos asignados
                <br><small>Asigna cursos para comenzar</small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Materias asignadas -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-book"></i>
                Materias Asignadas (<?php echo count($materias_asignadas); ?>)
            </h3>
            <?php if (hasRole('admin') || hasRole('directivo')): ?>
            <button class="btn btn-primary btn-sm" onclick="toggleForm('asignar-materia')" style="margin-left: 1rem;">
                <i class="fas fa-plus"></i> Asignar Materia
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Formulario para asignar materia -->
        <?php if (hasRole('admin') || hasRole('directivo')): ?>
        <div id="asignar-materia" class="form-section" style="display: none;">
            <form method="POST" class="form-container">
                <div class="form-row">
                    <div class="form-group">
                        <label for="asignar_curso_id">Curso: *</label>
                        <select name="curso_id" id="asignar_curso_id" required>
                            <option value="">Seleccionar curso</option>
                            <?php foreach ($cursos_asignados as $curso): ?>
                            <option value="<?php echo $curso['curso_id']; ?>" data-curso-id="<?php echo $curso['curso_id']; ?>">
                                <?php 
                                if ($curso['anio'] <= 3) {
                                    echo $curso['anio'] . '° ' . $curso['division'] . ' (' . $curso['turno'] . ')';
                                } else {
                                    echo $curso['anio'] . '° ' . $curso['division'] . ' - ' . $curso['especialidad'] . ' (' . $curso['turno'] . ')';
                                }
                                ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="asignar_materia_id">Materia: *</label>
                        <select name="materia_id" id="asignar_materia_id" required disabled>
                            <option value="">Primero selecciona un curso</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="asignar_materia" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Asignar Materia
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('asignar-materia')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        <div class="table-container">
            <?php if (!empty($materias_asignadas)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Año Académico</th>
                        <th>Materia</th>
                        <th>Curso</th>
                        <th>Fecha Asignación</th>
                        <?php if (hasRole('admin') || hasRole('directivo')): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materias_asignadas as $materia): ?>
                    <tr>
                        <td>
                            <span class="status status-info"><?php echo $materia['anio_academico']; ?></span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($materia['nombre']); ?></strong>
                        </td>
                        <td>
                            <?php if ($materia['curso'] === 'Sin curso específico'): ?>
                                <span class="status status-warning"><?php echo htmlspecialchars($materia['curso']); ?></span>
                                <br><small style="color: var(--secondary-color); font-style: italic;">Registro anterior - requiere actualización</small>
                            <?php else: ?>
                                <span class="status status-success"><?php echo htmlspecialchars($materia['curso']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?php echo date('d/m/Y', strtotime($materia['fecha_asignacion'])); ?></small>
                        </td>
                        <?php if (hasRole('admin') || hasRole('directivo')): ?>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de que desea desasignar esta materia del curso <?php echo htmlspecialchars($materia['curso']); ?>?')">
                                <input type="hidden" name="materia_curso_id" value="<?php echo $materia['id']; ?>">
                                <button type="submit" name="desasignar_materia" class="btn btn-sm btn-danger" title="Desasignar materia del curso">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="text-center" style="padding: 2rem; color: var(--secondary-color);">
                <i class="fas fa-book" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <br>No hay materias asignadas
                <br><small>Asigna materias para poder crear suplencias</small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Suplencias activas -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-exchange-alt"></i>
                Suplencias Activas (<?php echo count($suplencias_activas); ?>)
            </h3>
            <?php if (hasRole('admin') || hasRole('directivo')): ?>
            <button class="btn btn-primary btn-sm" onclick="toggleForm('nueva-suplencia')" style="margin-left: 1rem;">
                <i class="fas fa-plus"></i> Nueva Suplencia
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Formulario para nueva suplencia -->
        <?php if (hasRole('admin') || hasRole('directivo')): ?>
        <div id="nueva-suplencia" class="form-section" style="display: none;">
            <form method="POST" class="form-container">
                <div class="form-row">
                    <div class="form-group">
                        <label for="suplencia_materia_id">Materia:</label>
                        <select name="materia_id" id="suplencia_materia_id" required>
                            <option value="">Seleccionar materia</option>
                            <?php foreach ($materias_profesor as $materia): ?>
                            <option value="<?php echo $materia['materia_id']; ?>">
                                <?php echo htmlspecialchars($materia['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="suplencia_fecha_inicio">Fecha de Inicio:</label>
                        <input type="date" name="fecha_inicio" id="suplencia_fecha_inicio" required 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="suplencia_fecha_fin">Fecha de Fin (opcional):</label>
                        <input type="date" name="fecha_fin" id="suplencia_fecha_fin">
                    </div>
                    
                    <div class="form-group">
                        <label for="suplencia_motivo">Motivo:</label>
                        <input type="text" name="motivo" id="suplencia_motivo" required 
                               placeholder="Ej: Licencia médica, Capacitación, etc.">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="suplente_id">Suplente:</label>
                        <select name="suplente_id" id="suplente_id">
                            <option value="">Seleccionar suplente</option>
                            <?php foreach ($suplentes_disponibles as $suplente): ?>
                            <option value="<?php echo $suplente['id']; ?>">
                                <?php echo htmlspecialchars($suplente['apellido'] . ', ' . $suplente['nombre']); ?>
                                <?php if ($suplente['especialidad']): ?>
                                    (<?php echo htmlspecialchars($suplente['especialidad']); ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleForm('nuevo-suplente')" style="margin-top: 0.5rem;">
                            <i class="fas fa-plus"></i> Crear Nuevo Suplente
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="fuera_servicio" id="fuera_servicio">
                                <span class="checkmark"></span>
                                Fuera de servicio (sin suplente)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="crear_suplencia" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Suplencia
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('nueva-suplencia')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Formulario para nuevo suplente -->
        <div id="nuevo-suplente" class="form-section" style="display: none;">
            <form method="POST" class="form-container">
                <div class="form-row">
                    <div class="form-group">
                        <label for="suplente_dni">DNI: *</label>
                        <input type="text" name="dni" id="suplente_dni" required maxlength="20" 
                               placeholder="Ej: 12345678">
                    </div>
                    
                    <div class="form-group">
                        <label for="suplente_apellido">Apellido: *</label>
                        <input type="text" name="apellido" id="suplente_apellido" required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="suplente_nombre">Nombre: *</label>
                        <input type="text" name="nombre" id="suplente_nombre" required maxlength="100">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="suplente_telefono">Teléfono Celular:</label>
                        <input type="tel" name="telefono_celular" id="suplente_telefono" maxlength="20">
                    </div>
                    
                    <div class="form-group">
                        <label for="suplente_email">Email:</label>
                        <input type="email" name="email" id="suplente_email" maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="suplente_especialidad">Especialidad:</label>
                        <input type="text" name="especialidad" id="suplente_especialidad" maxlength="200" 
                               placeholder="Ej: Matemática, Física, etc.">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="guardar_suplente" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Suplente
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('nuevo-suplente')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="table-container">
            <?php if (!empty($suplencias_activas)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Suplente</th>
                        <th>Período</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <?php if (hasRole('admin') || hasRole('directivo')): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suplencias_activas as $suplencia): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($suplencia['materia']); ?></strong>
                        </td>
                        <td>
                            <?php if ($suplencia['fuera_servicio']): ?>
                                <span class="status status-danger">Fuera de servicio</span>
                            <?php elseif ($suplencia['suplente_apellido']): ?>
                                <strong><?php echo htmlspecialchars($suplencia['suplente_apellido'] . ', ' . $suplencia['suplente_nombre']); ?></strong>
                            <?php else: ?>
                                <span class="status status-warning">Sin asignar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo date('d/m/Y', strtotime($suplencia['fecha_inicio'])); ?></strong>
                            <?php if ($suplencia['fecha_fin']): ?>
                                <br><small>hasta <?php echo date('d/m/Y', strtotime($suplencia['fecha_fin'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?php echo htmlspecialchars($suplencia['motivo']); ?></small>
                        </td>
                        <td>
                            <span class="status status-info">Activa</span>
                        </td>
                        <?php if (hasRole('admin') || hasRole('directivo')): ?>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de que desea finalizar esta suplencia?')">
                                <input type="hidden" name="suplencia_id" value="<?php echo $suplencia['id']; ?>">
                                <button type="submit" name="finalizar_suplencia" class="btn btn-sm btn-success" title="Finalizar suplencia">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="text-center" style="padding: 2rem; color: var(--secondary-color);">
                <i class="fas fa-exchange-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <br>No hay suplencias activas
                <br><small>Crea una suplencia cuando sea necesario</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
function toggleForm(formId) {
    const form = document.getElementById(formId);
    if (!form) {
        console.error('Form not found:', formId);
        return;
    }
    
    const currentDisplay = window.getComputedStyle(form).display;
    if (currentDisplay === 'none') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}

// Filtrar materias según el curso seleccionado
function filtrarMateriasPorCurso() {
    const cursoSelect = document.getElementById('asignar_curso_id');
    const materiaSelect = document.getElementById('asignar_materia_id');
    
    if (!cursoSelect || !materiaSelect) return;
    
    const cursoId = cursoSelect.value;
    
    // Si no hay curso seleccionado, deshabilitar materias y limpiar opciones
    if (!cursoId) {
        materiaSelect.value = '';
        materiaSelect.disabled = true;
        // Limpiar todas las opciones excepto la primera
        while (materiaSelect.children.length > 1) {
            materiaSelect.removeChild(materiaSelect.lastChild);
        }
        return;
    }
    
    // Mostrar loading
    materiaSelect.disabled = true;
    materiaSelect.innerHTML = '<option value="">Cargando materias...</option>';
    
    // Obtener materias del curso mediante AJAX
    fetch(`profesor_ficha.php?id=<?php echo $profesor_id; ?>&ajax=get_materias_curso&curso_id=${cursoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Obtener materias ya asignadas para este curso (desde PHP)
                const materiasAsignadas = <?php echo json_encode($materias_asignadas); ?>;
                const materiasAsignadasCurso = materiasAsignadas
                    .filter(m => m.curso_id && m.curso_id == cursoId)
                    .map(m => parseInt(m.materia_id));
                
                // Limpiar el select
                materiaSelect.innerHTML = '<option value="">Seleccionar materia</option>';
                
                // Agregar solo las materias del curso que no estén ya asignadas
                data.materias.forEach(materia => {
                    if (!materiasAsignadasCurso.includes(parseInt(materia.id))) {
                        const option = document.createElement('option');
                        option.value = materia.id;
                        option.textContent = materia.nombre;
                        materiaSelect.appendChild(option);
                    }
                });
                
                // Habilitar el select
                materiaSelect.disabled = false;
                
                // Mostrar mensaje si no hay materias disponibles
                if (materiaSelect.children.length === 1) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No hay materias disponibles para este curso';
                    option.disabled = true;
                    materiaSelect.appendChild(option);
                }
                
            } else {
                materiaSelect.innerHTML = '<option value="">Error al cargar materias</option>';
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            materiaSelect.innerHTML = '<option value="">Error al cargar materias</option>';
            console.error('Error:', error);
        });
}



// Manejar checkbox "fuera de servicio"
document.addEventListener('DOMContentLoaded', function() {
    // Event listener para filtrado de materias por curso
    const cursoSelect = document.getElementById('asignar_curso_id');
    const materiaSelect = document.getElementById('asignar_materia_id');
    
    if (cursoSelect && materiaSelect) {
        // Deshabilitar materias inicialmente
        materiaSelect.disabled = true;
        
        // Agregar event listener
        cursoSelect.addEventListener('change', filtrarMateriasPorCurso);
        
        // Si hay un curso preseleccionado, cargar sus materias
        if (cursoSelect.value) {
            filtrarMateriasPorCurso();
        }
    }
    
    const fueraServicioCheckbox = document.getElementById('fuera_servicio');
    const suplenteSelect = document.getElementById('suplente_id');
    const suplenciaMateriaSelect = document.getElementById('suplencia_materia_id');
    
    if (fueraServicioCheckbox && suplenteSelect) {
        fueraServicioCheckbox.addEventListener('change', function() {
            if (this.checked) {
                suplenteSelect.value = '';
                suplenteSelect.disabled = true;
            } else {
                suplenteSelect.disabled = false;
            }
        });
    }
    
    // Filtrar suplentes por materia
    if (suplenciaMateriaSelect && suplenteSelect) {
        suplenciaMateriaSelect.addEventListener('change', function() {
            const materiaId = this.value;
            
            // Si no hay materia seleccionada, mostrar todos los suplentes
            if (!materiaId) {
                Array.from(suplenteSelect.options).forEach(option => {
                    option.style.display = '';
                });
                return;
            }
            
            // Obtener la materia seleccionada para filtrar por especialidad
            const materiaOption = this.options[this.selectedIndex];
            const materiaNombre = materiaOption.textContent.toLowerCase();
            
            // Filtrar suplentes por especialidad (aproximado)
            Array.from(suplenteSelect.options).forEach(option => {
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }
                
                const suplenteText = option.textContent.toLowerCase();
                const tieneEspecialidad = option.textContent.includes('(') && option.textContent.includes(')');
                
                // Mostrar suplentes que:
                // 1. No tienen especialidad específica (pueden cubrir cualquier materia)
                // 2. Tienen especialidad que coincide con la materia
                if (!tieneEspecialidad || suplenteText.includes(materiaNombre) || 
                    (materiaNombre.includes('matemática') && suplenteText.includes('matemática')) ||
                    (materiaNombre.includes('física') && suplenteText.includes('física')) ||
                    (materiaNombre.includes('química') && suplenteText.includes('química')) ||
                    (materiaNombre.includes('historia') && suplenteText.includes('historia')) ||
                    (materiaNombre.includes('lengua') && suplenteText.includes('lengua')) ||
                    (materiaNombre.includes('inglés') && suplenteText.includes('inglés'))) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Si no hay suplentes disponibles, sugerir "fuera de servicio"
            const suplentesDisponibles = Array.from(suplenteSelect.options).filter(option => 
                option.style.display !== 'none' && option.value !== ''
            );
            
            if (suplentesDisponibles.length === 0 && fueraServicioCheckbox && !fueraServicioCheckbox.checked) {
                alert('No hay suplentes disponibles para esta materia. Considere marcar "Fuera de servicio".');
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
