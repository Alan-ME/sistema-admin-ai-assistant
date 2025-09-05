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

$pageTitle = 'Ficha del Estudiante (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';
include 'includes/header.php';

$estudiante_id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';
$success_message = '';
$error_message = '';

if (!$estudiante_id) {
    header('Location: estudiantes.php');
    exit();
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estudiante'])) {
    try {
        $db->query("
            UPDATE estudiantes SET 
                telefono_fijo = ?, telefono_celular = ?, email = ?, domicilio = ?
            WHERE id = ?
        ", [
            $_POST['telefono_fijo'] ?: null,
            $_POST['telefono_celular'] ?: null,
            $_POST['email'] ?: null,
            $_POST['domicilio'] ?: null,
            $estudiante_id
        ]);
        
        $success_message = "Información del estudiante actualizada correctamente";
        $action = ''; // Volver a vista normal
    } catch (Exception $e) {
        $error_message = "Error al actualizar estudiante: " . $e->getMessage();
    }
}

// Procesar formulario de responsable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_responsable'])) {
    try {
        $db->query("
            INSERT INTO responsables (estudiante_id, nombre, apellido, dni, telefono_celular, email, parentesco, es_contacto_emergencia)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $estudiante_id,
            $_POST['nombre'],
            $_POST['apellido'],
            $_POST['dni'] ?: null,
            $_POST['telefono'],
            $_POST['email'] ?: null,
            $_POST['parentesco'],
            isset($_POST['es_contacto_emergencia']) ? 1 : 0
        ]);
        
        $success_message = "Responsable agregado correctamente";
    } catch (Exception $e) {
        $error_message = "Error al agregar responsable: " . $e->getMessage();
    }
}

// Procesar formulario de contacto de emergencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_contacto'])) {
    try {
        $db->query("
            INSERT INTO contactos_emergencia (estudiante_id, nombre, telefono, parentesco)
            VALUES (?, ?, ?, ?)
        ", [
            $estudiante_id,
            $_POST['nombre'],
            $_POST['telefono'],
            $_POST['parentesco']
        ]);
        
        $success_message = "Contacto de emergencia agregado correctamente";
    } catch (Exception $e) {
        $error_message = "Error al agregar contacto: " . $e->getMessage();
    }
}

// Eliminar responsable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_responsable'])) {
    try {
        $db->query("DELETE FROM responsables WHERE id = ? AND estudiante_id = ?", [
            $_POST['responsable_id'],
            $estudiante_id
        ]);
        $success_message = "Responsable eliminado correctamente";
    } catch (Exception $e) {
        $error_message = "Error al eliminar responsable: " . $e->getMessage();
    }
}

// Eliminar contacto de emergencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_contacto'])) {
    try {
        $db->query("DELETE FROM contactos_emergencia WHERE id = ? AND estudiante_id = ?", [
            $_POST['contacto_id'],
            $estudiante_id
        ]);
        $success_message = "Contacto de emergencia eliminado correctamente";
    } catch (Exception $e) {
        $error_message = "Error al eliminar contacto: " . $e->getMessage();
    }
}

// Cambiar curso por mal comportamiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_curso'])) {
    try {
        // Verificar que el estudiante tenga más de 3 amonestaciones verbales
        $llamados_amonestacion_verbal = $db->fetch("SELECT COUNT(*) as total FROM llamados_atencion WHERE estudiante_id = ? AND sancion = 'Amonestación verbal'", [$estudiante_id])['total'];
        
        if ($llamados_amonestacion_verbal <= 3) {
            throw new Exception('El estudiante debe tener más de 3 amonestaciones verbales para ser cambiado de curso');
        }
        
        $nuevo_curso_id = $_POST['nuevo_curso_id'];
        
        // Verificar que el nuevo curso existe y es del mismo año
        $nuevo_curso = $db->fetch("
            SELECT c.*, esp.nombre as especialidad 
            FROM cursos c 
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id 
            WHERE c.id = ? AND c.activo = 1
        ", [$nuevo_curso_id]);
        
        if (!$nuevo_curso) {
            throw new Exception('El curso seleccionado no existe o no está activo');
        }
        
        if ($nuevo_curso['anio'] != $estudiante['anio']) {
            throw new Exception('Solo se puede cambiar a un curso del mismo año');
        }
        
        // Iniciar transacción
        $db->beginTransaction();
        
        try {
            // Limpiar registros relacionados con el curso anterior
            // Solo eliminar materias previas del curso anterior
            // Las notas se mantienen para no perder el progreso académico
            $db->query("DELETE FROM materias_previas WHERE estudiante_id = ?", [$estudiante_id]);
            
            // 3. Actualizar el curso del estudiante
            $db->query("UPDATE estudiantes SET curso_id = ? WHERE id = ?", [$nuevo_curso_id, $estudiante_id]);
            
            // 4. Registrar el cambio en un log (opcional)
            $db->query("
                INSERT INTO llamados_atencion (estudiante_id, fecha, motivo, sancion, observaciones, usuario_id) 
                VALUES (?, CURDATE(), 'Cambio de curso por mal comportamiento', 'Cambio de división', ?, ?)
            ", [$estudiante_id, "Cambiado de " . $estudiante['anio'] . "° " . $estudiante['division'] . " a " . $nuevo_curso['anio'] . "° " . $nuevo_curso['division'], $_SESSION['usuario_id']]);
            
            $db->commit();
            $success_message = "Estudiante cambiado exitosamente de " . $estudiante['anio'] . "° " . $estudiante['division'] . " a " . $nuevo_curso['anio'] . "° " . $nuevo_curso['division'];
            
            // Recargar los datos del estudiante
            $estudiante = $db->fetch("
                SELECT e.*, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno
                FROM estudiantes e
                LEFT JOIN cursos c ON e.curso_id = c.id
                LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
                LEFT JOIN turnos t ON c.turno_id = t.id
                WHERE e.id = ? AND e.activo = 1
            ", [$estudiante_id]);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error_message = "Error al cambiar curso: " . $e->getMessage();
    }
}

// Obtener datos del estudiante
$estudiante = $db->fetch("
    SELECT e.*, c.anio, c.division, esp.nombre as especialidad, t.nombre as turno
    FROM estudiantes e
    LEFT JOIN cursos c ON e.curso_id = c.id
    LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
    LEFT JOIN turnos t ON c.turno_id = t.id
    WHERE e.id = ? AND e.activo = 1
", [$estudiante_id]);

if (!$estudiante) {
    header('Location: estudiantes.php');
    exit();
}

// Obtener llamados de atención
$llamados = $db->fetchAll("
    SELECT l.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido
    FROM llamados_atencion l
    LEFT JOIN usuarios u ON l.usuario_id = u.id
    WHERE l.estudiante_id = ?
    ORDER BY l.fecha DESC
    LIMIT 20
", [$estudiante_id]);

// Obtener responsables
$responsables = $db->fetchAll("
    SELECT * FROM responsables WHERE estudiante_id = ? ORDER BY es_contacto_emergencia DESC
", [$estudiante_id]);

// Obtener contactos de emergencia
$contactos_emergencia = $db->fetchAll("
    SELECT * FROM contactos_emergencia WHERE estudiante_id = ?
", [$estudiante_id]);

// Estadísticas del estudiante
$stats = [];
$stats['llamados_total'] = $db->fetch("SELECT COUNT(*) as total FROM llamados_atencion WHERE estudiante_id = ?", [$estudiante_id])['total'];
$stats['llamados_mes'] = $db->fetch("SELECT COUNT(*) as total FROM llamados_atencion WHERE estudiante_id = ? AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())", [$estudiante_id])['total'];

// Verificar si el estudiante puede ser cambiado de curso por mal comportamiento
$llamados_amonestacion_verbal = $db->fetch("SELECT COUNT(*) as total FROM llamados_atencion WHERE estudiante_id = ? AND sancion = 'Amonestación verbal'", [$estudiante_id])['total'];
$puede_cambiar_curso = $llamados_amonestacion_verbal > 3;

// Obtener cursos disponibles para el cambio (mismo año, diferente división)
$cursos_disponibles = [];
if ($puede_cambiar_curso && $estudiante['curso_id']) {
    $cursos_disponibles = $db->fetchAll("
        SELECT c.*, esp.nombre as especialidad, t.nombre as turno
        FROM cursos c
        LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
        LEFT JOIN turnos t ON c.turno_id = t.id
        WHERE c.anio = ? AND c.id != ? AND c.activo = 1
        ORDER BY c.division
    ", [$estudiante['anio'], $estudiante['curso_id']]);
}

// Obtener notas del estudiante para boletín
$notas_estudiante = [];
if ($estudiante['curso_id']) {
    // Obtener solo las materias que están asignadas al curso del estudiante
    $materias = $db->fetchAll("
        SELECT m.* FROM materias m
        INNER JOIN materia_curso mc ON m.id = mc.materia_id
        WHERE mc.curso_id = ? AND m.activa = 1
        ORDER BY m.nombre
    ", [$estudiante['curso_id']]);
    
    // Obtener notas de todos los cuatrimestres
    $notas_raw = $db->fetchAll("
        SELECT n.*, m.nombre as materia_nombre
        FROM notas n
        LEFT JOIN materias m ON n.materia_id = m.id
        WHERE n.estudiante_id = ? AND n.cuatrimestre IN (1, 2, 3)
ORDER BY n.cuatrimestre, m.nombre
    ", [$estudiante_id]);
    
    // Organizar notas por cuatrimestre y materia
    $notas_organizadas = [];
    foreach ($materias as $materia) {
        $notas_organizadas[$materia['id']] = [
            'materia' => $materia,
            'cuatrimestres' => [1 => null, 2 => null, 3 => null],
            'promedio' => null
        ];
    }
    
    // Llenar con las notas existentes
    foreach ($notas_raw as $nota) {
        if (isset($notas_organizadas[$nota['materia_id']])) {
            $notas_organizadas[$nota['materia_id']]['cuatrimestres'][$nota['cuatrimestre']] = $nota['nota'];
        }
    }
    
    // Calcular promedios solo cuando se tienen todos los cuatrimestres
    foreach ($notas_organizadas as $materia_id => &$datos) {
        $notas_validas = array_filter($datos['cuatrimestres'], function($nota) {
            return $nota !== null;
        });
        // Solo calcular promedio si se tienen los 3 cuatrimestres
        if (count($notas_validas) === 3) {
            $datos['promedio'] = round(array_sum($notas_validas) / count($notas_validas), 2);
            $datos['promedio_calculado'] = true;
        } else {
            $datos['promedio'] = null;
            $datos['promedio_calculado'] = false;
        }
    }
    
    // Calcular si está disponible para pasar de año (más de la mitad de materias aprobadas)
    $total_materias = count($materias);
    $materias_aprobadas = 0;
    $materias_con_notas = 0;
    
    foreach ($notas_organizadas as $datos) {
        if ($datos['promedio_calculado']) {
            $materias_con_notas++;
            if ($datos['promedio'] >= 7) {
                $materias_aprobadas++;
            }
        }
    }
    
    $disponible_pasar_anio = false;
    $porcentaje_aprobadas = 0;
    
    if ($materias_con_notas > 0) {
        $porcentaje_aprobadas = round(($materias_aprobadas / $total_materias) * 100, 1);
        $disponible_pasar_anio = $materias_aprobadas > ($total_materias / 2);
    }
    
    $notas_estudiante = $notas_organizadas;
}
?>

<section class="ficha-estudiante">
    <?php if ($success_message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <?php if ($estudiante['curso_id'] && !empty($notas_estudiante) && $disponible_pasar_anio): ?>
        <div class="alert alert-success" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none;">
            <i class="fas fa-trophy" style="font-size: 1.2em;"></i> 
            <strong>¡Felicitaciones!</strong> El estudiante está disponible para pasar de año con <?php echo $materias_aprobadas; ?> de <?php echo $total_materias; ?> materias aprobadas (<?php echo $porcentaje_aprobadas; ?>%).
        </div>
    <?php elseif ($estudiante['curso_id'] && !empty($notas_estudiante) && $materias_aprobadas > 0): ?>
        <div class="alert alert-info" style="background: linear-gradient(135deg, #17a2b8, #6f42c1); color: white; border: none;">
            <i class="fas fa-info-circle" style="font-size: 1.2em;"></i> 
            <strong>Progreso:</strong> El estudiante tiene <?php echo $materias_aprobadas; ?> de <?php echo $total_materias; ?> materias aprobadas (<?php echo $porcentaje_aprobadas; ?>%). 
            Necesita aprobar <?php echo ceil($total_materias / 2) - $materias_aprobadas; ?> materia<?php echo (ceil($total_materias / 2) - $materias_aprobadas) != 1 ? 's' : ''; ?> más para pasar de año.
        </div>
    <?php elseif ($estudiante['curso_id'] && !empty($notas_estudiante) && $materias_aprobadas == 0): ?>
        <div class="alert alert-warning" style="background: linear-gradient(135deg, #ffc107, #fd7e14); color: white; border: none;">
            <i class="fas fa-exclamation-triangle" style="font-size: 1.2em;"></i> 
            <strong>Atención:</strong> El estudiante no tiene materias aprobadas. Necesita aprobar al menos <?php echo ceil($total_materias / 2); ?> de <?php echo $total_materias; ?> materias para pasar de año.
        </div>
    <?php endif; ?>
    
    <?php if ($puede_cambiar_curso && $estudiante['curso_id']): ?>
        <div class="alert alert-danger" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none;">
            <i class="fas fa-exclamation-triangle" style="font-size: 1.2em;"></i> 
            <strong>Cambio de Curso Disponible:</strong> El estudiante tiene <?php echo $llamados_amonestacion_verbal; ?> amonestaciones verbales (más de 3). 
            Se puede proceder con el cambio de curso por mal comportamiento.
            <?php if (hasRole('admin') || hasRole('directivo')): ?>
                <button type="button" class="btn btn-outline-light btn-sm" style="margin-left: 1rem;" onclick="openCambioCursoModal()">
                    <i class="fas fa-exchange-alt"></i> Cambiar de Curso
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    


    <div class="section-header">
        <h2>Ficha del Estudiante</h2>
        <div class="header-actions">
            <a href="estudiantes.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button type="button" class="btn btn-warning" onclick="openEditModal()">
                <i class="fas fa-edit"></i> Editar Información
            </button>
            <a href="llamados.php?action=nuevo&estudiante=<?php echo $estudiante['id']; ?>" class="btn btn-danger">
                <i class="fas fa-exclamation-triangle"></i> Nuevo Llamado
            </a>
            <?php if (hasRole('admin') || hasRole('directivo')): ?>
            <a href="notas.php?estudiante=<?php echo $estudiante['id']; ?>" class="btn btn-primary">
                <i class="fas fa-clipboard-check"></i> Ver Notas
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información personal -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Información Personal</h3>
        </div>
        <div class="card-body">
            <div class="student-profile">
                <div class="profile-photo">
                    <?php if ($estudiante['foto']): ?>
                        <img src="<?php echo htmlspecialchars($estudiante['foto']); ?>" alt="Foto del estudiante">
                    <?php else: ?>
                        <div class="default-photo">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']); ?></h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>DNI:</strong>
                            <span><?php echo htmlspecialchars($estudiante['dni']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Fecha de Nacimiento:</strong>
                            <span>
                                <?php if ($estudiante['fecha_nacimiento']): ?>
                                    <?php echo date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])); ?>
                                    (<?php echo floor((time() - strtotime($estudiante['fecha_nacimiento'])) / (365.25 * 24 * 3600)); ?> años)
                                <?php else: ?>
                                    No registrada
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Curso:</strong>
                            <span>
                                <?php if ($estudiante['anio']): ?>
                                    <?php echo $estudiante['anio'] . '° ' . $estudiante['division'] . ' - ' . $estudiante['especialidad']; ?>
                                    <small>(<?php echo $estudiante['turno']; ?>)</small>
                                <?php else: ?>
                                    <span class="status status-warning">Sin asignar</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Grupo Sanguíneo:</strong>
                            <span><?php echo $estudiante['grupo_sanguineo'] ?: 'No registrado'; ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Obra Social:</strong>
                            <span><?php echo htmlspecialchars($estudiante['obra_social']) ?: 'No registrada'; ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Fecha de Ingreso:</strong>
                            <span><?php echo $estudiante['fecha_ingreso'] ? date('d/m/Y', strtotime($estudiante['fecha_ingreso'])) : 'No registrada'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-info">
                <h4>Información de Contacto</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Domicilio:</strong>
                        <span><?php echo htmlspecialchars($estudiante['domicilio']) ?: 'No registrado'; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Teléfono Fijo:</strong>
                        <span><?php echo htmlspecialchars($estudiante['telefono_fijo']) ?: 'No registrado'; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Teléfono Celular:</strong>
                        <span><?php echo htmlspecialchars($estudiante['telefono_celular']) ?: 'No registrado'; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Email:</strong>
                        <span><?php echo htmlspecialchars($estudiante['email']) ?: 'No registrado'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['llamados_total']; ?></h3>
                <p>Total Llamados</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['llamados_mes']; ?></h3>
                <p>Llamados Este Mes</p>
            </div>
        </div>
        <?php if ($estudiante['curso_id'] && !empty($notas_estudiante)): ?>
        <div class="stat-card">
            <div class="stat-icon <?php echo $disponible_pasar_anio ? 'success' : 'warning'; ?>">
                <i class="fas <?php echo $disponible_pasar_anio ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $materias_aprobadas; ?>/<?php echo $total_materias; ?></h3>
                <p><?php echo $disponible_pasar_anio ? 'Disponible para pasar de año' : 'Pendiente de aprobación'; ?></p>
                <small style="color: var(--secondary-color);"><?php echo $porcentaje_aprobadas; ?>% aprobadas</small>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid" style="grid-template-columns: 1fr; gap: 2rem;">
        <!-- Llamados de atención recientes -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Llamados de Atención Recientes</h3>
                <a href="llamados.php?estudiante=<?php echo $estudiante['id']; ?>" class="btn btn-sm btn-secondary" style="margin-left: 1rem;">Ver todos</a>
            </div>
            <div class="card-body">
                <?php if (!empty($llamados)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Motivo</th>
                                <th>Sanción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($llamados, 0, 10) as $llamado): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($llamado['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars(substr($llamado['motivo'], 0, 40)) . (strlen($llamado['motivo']) > 40 ? '...' : ''); ?></td>
                                <td>
                                    <?php if ($llamado['sancion']): ?>
                                        <span class="status status-warning"><?php echo htmlspecialchars($llamado['sancion']); ?></span>
                                    <?php else: ?>
                                        <span class="status status-success">Sin sanción</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-center" style="color: var(--secondary-color); padding: 2rem;">No hay llamados registrados</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Responsables y Contactos -->
    <?php if (!empty($responsables) || !empty($contactos_emergencia)): ?>
    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Responsables -->
        <?php if (!empty($responsables)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Responsables</h3>
            </div>
            <div class="card-body">
                <?php foreach ($responsables as $responsable): ?>
                <div class="responsable-item" style="padding: 1rem; border: 1px solid var(--medium-gray); border-radius: var(--border-radius); margin-bottom: 1rem; position: relative;">
                    <div style="position: absolute; top: 0.5rem; right: 0.5rem;">
                        <button type="button" class="btn-eliminar" onclick="confirmarEliminarResponsable(<?php echo $responsable['id']; ?>, '<?php echo htmlspecialchars($responsable['apellido'] . ', ' . $responsable['nombre']); ?>')" title="Eliminar responsable">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <h4 style="margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($responsable['apellido'] . ', ' . $responsable['nombre']); ?>
                        <?php if ($responsable['es_contacto_emergencia']): ?>
                            <span class="status status-success" style="font-size: 0.75rem;">Contacto de Emergencia</span>
                        <?php endif; ?>
                    </h4>
                    <div class="info-grid" style="font-size: 0.875rem;">
                        <div><strong>DNI:</strong> <?php echo htmlspecialchars($responsable['dni']); ?></div>
                        <div><strong>Parentesco:</strong> <?php echo htmlspecialchars($responsable['parentesco']); ?></div>
                        <div><strong>Teléfono:</strong> <?php echo htmlspecialchars($responsable['telefono_celular'] ?: $responsable['telefono_fijo']); ?></div>
                        <div><strong>Ocupación:</strong> <?php echo htmlspecialchars($responsable['ocupacion']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contactos de emergencia -->
        <?php if (!empty($contactos_emergencia)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Contactos de Emergencia</h3>
            </div>
            <div class="card-body">
                <?php foreach ($contactos_emergencia as $contacto): ?>
                <div class="contacto-item" style="padding: 1rem; border: 1px solid var(--medium-gray); border-radius: var(--border-radius); margin-bottom: 1rem; position: relative;">
                    <div style="position: absolute; top: 0.5rem; right: 0.5rem;">
                        <button type="button" class="btn-eliminar" onclick="confirmarEliminarContacto(<?php echo $contacto['id']; ?>, '<?php echo htmlspecialchars($contacto['nombre']); ?>')" title="Eliminar contacto">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($contacto['nombre']); ?></h4>
                    <div class="info-grid" style="font-size: 0.875rem;">
                        <div><strong>Teléfono:</strong> <?php echo htmlspecialchars($contacto['telefono']); ?></div>
                        <div><strong>Parentesco:</strong> <?php echo htmlspecialchars($contacto['parentesco']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Boletín de Notas -->
    <div class="card">
        <div class="card-header" style="padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">
                    📊 Boletín de Notas - <?php echo $estudiante['anio'] . '° ' . $estudiante['division'] . ' - ' . $estudiante['especialidad']; ?>
                </h3>
                <?php if (!empty($notas_estudiante)): ?>
                <button type="button" class="btn btn-success" onclick="imprimirBoletin()" style="margin-left: 20px;">
                    <i class="fas fa-print"></i> Imprimir Boletín
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="boletin-estudiante-container">
            <table class="boletin-estudiante-table">
                <thead>
                    <tr>
                        <th class="materia-header">Materia</th>
                        <th class="cuatrimestre-header">1° Cuatrimestre</th>
<th class="cuatrimestre-header">2° Cuatrimestre</th>
<th class="cuatrimestre-header">3° Cuatrimestre</th>
                        <th class="promedio-header">Promedio</th>
                        <th class="estado-header">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notas_estudiante as $materia_id => $datos): ?>
                    <tr>
                        <td class="materia-cell">
                            <strong><?php echo htmlspecialchars($datos['materia']['nombre']); ?></strong>
                        </td>
                        <td class="nota-cell">
                            <span class="nota-value"><?php echo $datos['cuatrimestres'][1] ?? '-'; ?></span>
                        </td>
                        <td class="nota-cell">
                            <span class="nota-value"><?php echo $datos['cuatrimestres'][2] ?? '-'; ?></span>
                        </td>
                        <td class="nota-cell">
                            <span class="nota-value"><?php echo $datos['cuatrimestres'][3] ?? '-'; ?></span>
                        </td>
                        <td class="promedio-cell">
                            <?php if ($datos['promedio_calculado']): ?>
                                <span class="promedio-value"><?php echo $datos['promedio']; ?></span>
                            <?php else: ?>
                                <span class="promedio-pendiente">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td class="estado-cell">
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
        <?php if (empty($notas_estudiante)): ?>
        <div class="card-body">
            <div class="text-center" style="padding: 3rem;">
                <i class="fas fa-clipboard-list" style="font-size: 3rem; color: var(--secondary-color); margin-bottom: 1rem; opacity: 0.5;"></i>
                <h4 style="color: var(--secondary-color); margin-bottom: 1rem;">No hay notas registradas</h4>
                <p style="color: var(--secondary-color); margin-bottom: 1.5rem;">
                    <?php if (!$estudiante['curso_id']): ?>
                        <strong>El estudiante no tiene curso asignado.</strong><br>
                        <small>Asigne un curso al estudiante para poder registrar notas.</small>
                    <?php else: ?>
                        <strong>No se han registrado notas para este estudiante.</strong><br>
                        <small>Las notas aparecerán aquí una vez que sean cargadas por los profesores.</small>
                    <?php endif; ?>
                </p>
                <?php if (!$estudiante['curso_id']): ?>
                <button type="button" class="btn btn-primary" onclick="abrirModal('modalCambioCurso')">
                    <i class="fas fa-graduation-cap"></i> Asignar Curso
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal de confirmación para eliminar responsable -->
<div id="modalEliminarResponsable" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h3>
            <span class="close" onclick="cerrarModal('modalEliminarResponsable')">&times;</span>
        </div>
        <div class="modal-body">
            <p>¿Estás seguro de que deseas eliminar al responsable <strong id="nombreResponsable"></strong>?</p>
            <p style="color: #dc3545; font-size: 0.9rem;"><i class="fas fa-info-circle"></i> Esta acción no se puede deshacer.</p>
            <form method="POST" style="margin-top: 1.5rem;">
                <input type="hidden" name="responsable_id" id="responsableId">
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEliminarResponsable')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="eliminar_responsable" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar contacto -->
<div id="modalEliminarContacto" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h3>
            <span class="close" onclick="cerrarModal('modalEliminarContacto')">&times;</span>
        </div>
        <div class="modal-body">
            <p>¿Estás seguro de que deseas eliminar el contacto de emergencia <strong id="nombreContacto"></strong>?</p>
            <p style="color: #dc3545; font-size: 0.9rem;"><i class="fas fa-info-circle"></i> Esta acción no se puede deshacer.</p>
            <form method="POST" style="margin-top: 1.5rem;">
                <input type="hidden" name="contacto_id" id="contactoId">
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEliminarContacto')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="eliminar_contacto" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de cambio de curso -->
<div id="modalCambioCurso" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-exchange-alt"></i> Cambio de Curso por Mal Comportamiento</h3>
            <span class="close" onclick="cerrarModal('modalCambioCurso')">&times;</span>
        </div>
        <div class="modal-body">
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                <h4 style="color: #856404; margin: 0 0 0.5rem 0;">
                    <i class="fas fa-exclamation-triangle"></i> Información Importante
                </h4>
                <ul style="color: #856404; margin: 0; padding-left: 1.5rem;">
                    <li>El estudiante tiene <strong><?php echo $llamados_amonestacion_verbal; ?> amonestaciones verbales</strong></li>
                    <li>Se <strong>mantendrán todas las notas</strong> del curso actual</li>
                    <li>Se eliminarán las materias previas del curso actual</li>
                    <li>Se registrará un llamado de atención por el cambio de curso</li>
                    <li>El estudiante conservará su progreso académico</li>
                </ul>
            </div>
            
            <form method="POST" style="margin-top: 1.5rem;">
                <div class="form-group">
                    <label for="nuevo_curso_id">Nuevo Curso (mismo año):</label>
                    <select name="nuevo_curso_id" id="nuevo_curso_id" required class="form-control">
                        <option value="">Seleccionar curso</option>
                        <?php foreach ($cursos_disponibles as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>">
                            <?php echo $curso['anio'] . '° ' . $curso['division'] . ' - ' . $curso['especialidad'] . ' (' . $curso['turno'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCambioCurso')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="cambiar_curso" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas cambiar al estudiante de curso? Se mantendrán las notas pero se eliminarán las materias previas del curso actual.')">
                        <i class="fas fa-exchange-alt"></i> Confirmar Cambio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.student-profile {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
}

.profile-photo {
    flex-shrink: 0;
}

.profile-photo img,
.default-photo {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--primary-color);
}

.default-photo {
    background: var(--light-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: var(--secondary-color);
}

.profile-info h2 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-item strong {
    color: var(--text-color);
    font-size: 0.875rem;
}

.info-item span {
    color: var(--secondary-color);
}

.contact-info {
    border-top: 1px solid var(--medium-gray);
    padding-top: 2rem;
}

.contact-info h4 {
    color: var(--text-color);
    margin-bottom: 1rem;
    font-size: 1.125rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

/* ===== ESTILOS PARA EL BOLETÍN DEL ESTUDIANTE ===== */

.boletin-estudiante-container {
    overflow-x: auto;
    margin: 20px 0;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    padding: 3px;
}

.boletin-estudiante-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.boletin-estudiante-table th,
.boletin-estudiante-table td {
    border: none;
    padding: 12px 8px;
    text-align: center;
    vertical-align: middle;
    position: relative;
}

.boletin-estudiante-table th {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: white;
    font-weight: 700;
    font-size: 0.9em;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.materia-header {
    text-align: left !important;
    min-width: 180px;
}

.cuatrimestre-header {
    min-width: 100px;
}

.promedio-header {
    min-width: 100px;
    background: linear-gradient(135deg, #065f46 0%, #064e3b 100%) !important;
}

.estado-header {
    min-width: 120px;
}

.materia-cell {
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

.promedio-cell {
    padding: 8px 4px !important;
    background: linear-gradient(135deg, rgba(6, 95, 70, 0.1) 0%, rgba(6, 78, 59, 0.1) 100%);
    border-left: 2px solid #065f46;
    border-right: 2px solid #065f46;
    font-weight: 700;
}

.promedio-cell:hover {
    background: linear-gradient(135deg, rgba(6, 95, 70, 0.15) 0%, rgba(6, 78, 59, 0.15) 100%);
    transform: scale(1.02);
    box-shadow: 0 2px 8px rgba(6, 95, 70, 0.2);
}

.estado-cell {
    padding: 8px 4px !important;
    background: white;
    transition: all 0.3s ease;
}

.nota-value {
    font-weight: 700;
    font-size: 1.1em;
    color: #495057;
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-width: 40px;
    transition: all 0.3s ease;
}

.promedio-value {
    font-weight: 800;
    font-size: 1.2em;
    color: #065f46;
    display: inline-block;
    padding: 6px 10px;
    border-radius: 8px;
    background: linear-gradient(135deg, rgba(6, 95, 70, 0.1) 0%, rgba(6, 78, 59, 0.1) 100%);
    min-width: 50px;
    border: 2px solid #065f46;
    box-shadow: 0 2px 8px rgba(6, 95, 70, 0.2);
}

.promedio-pendiente {
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

    .estado {
        font-weight: 700;
        font-size: 0.9em;
        padding: 6px 12px;
        border-radius: 20px;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Estilos para formularios de edición */
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        font-weight: 500;
        color: var(--text-color);
    }

    .checkbox-label input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
    }

    .form-container h4 {
        color: var(--primary-color);
        margin-bottom: 1rem;
        font-size: 1.1rem;
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
    }

    .form-container hr {
        border: none;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--medium-gray), transparent);
        margin: 2rem 0;
    }

.estado.aprobado {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
}

.estado.reprobado {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.estado.pendiente {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.boletin-estudiante-table tbody tr {
    transition: all 0.3s ease;
}

.boletin-estudiante-table tbody tr:hover {
    background: linear-gradient(135deg, #f0f4ff 0%, #dbeafe 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.1);
}

.boletin-estudiante-table tbody tr:hover .materia-cell {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.boletin-estudiante-table tbody tr {
    animation: fadeIn 0.5s ease-out;
}

.boletin-estudiante-table tbody tr:nth-child(even) {
    animation-delay: 0.1s;
}

.boletin-estudiante-table tbody tr:nth-child(odd) {
    animation-delay: 0.2s;
}

/* Scrollbar personalizado */
.boletin-estudiante-container::-webkit-scrollbar {
    height: 8px;
}

.boletin-estudiante-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.boletin-estudiante-container::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 4px;
}

.boletin-estudiante-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
}

/* Responsive */
@media (max-width: 768px) {
    .boletin-estudiante-table th,
    .boletin-estudiante-table td {
        padding: 8px 4px;
        font-size: 0.85em;
    }
    
    .materia-header {
        min-width: 120px;
    }
    
    .cuatrimestre-header,
    .promedio-header {
        min-width: 80px;
    }
    
    .estado-header {
        min-width: 100px;
    }
    
    .nota-value {
        font-size: 1em;
        min-width: 35px;
        padding: 3px 6px;
    }
    
    .promedio-value {
        font-size: 1.1em;
        min-width: 40px;
        padding: 4px 8px;
    }
    
    .estado {
        font-size: 0.8em;
        padding: 4px 8px;
    }
}

@media (max-width: 768px) {
    .student-profile {
        flex-direction: column;
        text-align: center;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .header-actions {
        flex-direction: column;
    }
    
    .grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<!-- Modal de Edición -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📝 Editar Información del Estudiante</h3>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Formulario de información de contacto -->
            <form method="POST" class="form-container">
                <h4>Información de Contacto</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="domicilio">Domicilio:</label>
                        <textarea name="domicilio" id="domicilio" placeholder="Dirección completa"><?php echo htmlspecialchars($estudiante['domicilio'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="telefono_fijo">Teléfono Fijo:</label>
                        <input type="tel" name="telefono_fijo" id="telefono_fijo" value="<?php echo htmlspecialchars($estudiante['telefono_fijo'] ?? ''); ?>" maxlength="20">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono_celular">Teléfono Celular:</label>
                        <input type="tel" name="telefono_celular" id="telefono_celular" value="<?php echo htmlspecialchars($estudiante['telefono_celular'] ?? ''); ?>" maxlength="20">
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($estudiante['email'] ?? ''); ?>" maxlength="100">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="actualizar_estudiante" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>

            <hr style="margin: 2rem 0; border: 1px solid var(--medium-gray);">

            <!-- Formulario para agregar responsable -->
            <form method="POST" class="form-container">
                <h4>➕ Agregar Responsable</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_resp">Nombre:</label>
                        <input type="text" name="nombre" id="nombre_resp" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="apellido_resp">Apellido:</label>
                        <input type="text" name="apellido" id="apellido_resp" required maxlength="50">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="dni_resp">DNI:</label>
                        <input type="text" name="dni" id="dni_resp" maxlength="20">
                    </div>
                    <div class="form-group">
                        <label for="telefono_resp">Teléfono:</label>
                        <input type="tel" name="telefono" id="telefono_resp" required maxlength="20">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email_resp">Email:</label>
                        <input type="email" name="email" id="email_resp" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="parentesco_resp">Parentesco:</label>
                        <select name="parentesco" id="parentesco_resp" required>
                            <option value="">Seleccionar</option>
                            <option value="Padre">Padre</option>
                            <option value="Madre">Madre</option>
                            <option value="Tutor">Tutor</option>
                            <option value="Abuelo/a">Abuelo/a</option>
                            <option value="Hermano/a">Hermano/a</option>
                            <option value="Tío/a">Tío/a</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="es_contacto_emergencia" value="1">
                            Es contacto de emergencia
                        </label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="guardar_responsable" class="btn btn-success">
                        <i class="fas fa-plus"></i> Agregar Responsable
                    </button>
                </div>
            </form>

            <hr style="margin: 2rem 0; border: 1px solid var(--medium-gray);">

            <!-- Formulario para agregar contacto de emergencia -->
            <form method="POST" class="form-container">
                <h4>🚨 Agregar Contacto de Emergencia</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_contacto">Nombre:</label>
                        <input type="text" name="nombre" id="nombre_contacto" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="telefono_contacto">Teléfono:</label>
                        <input type="tel" name="telefono" id="telefono_contacto" required maxlength="20">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="parentesco_contacto">Parentesco:</label>
                        <select name="parentesco" id="parentesco_contacto" required>
                            <option value="">Seleccionar</option>
                            <option value="Padre">Padre</option>
                            <option value="Madre">Madre</option>
                            <option value="Tutor">Tutor</option>
                            <option value="Abuelo/a">Abuelo/a</option>
                            <option value="Hermano/a">Hermano/a</option>
                            <option value="Tío/a">Tío/a</option>
                            <option value="Vecino/a">Vecino/a</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="guardar_contacto" class="btn btn-danger">
                        <i class="fas fa-plus"></i> Agregar Contacto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funciones para el modal
function openEditModal() {
    document.getElementById('editModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEditModal();
    }
});
</script>

<style>
/* Estilos para el modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease-out;
}

.modal-content {
    background-color: white;
    margin: 2% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease-out;
    position: relative;
}

.modal-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.close {
    color: white;
    font-size: 1.5rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    line-height: 1;
    text-align: center;
}

.close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

.modal-body {
    padding: 2rem;
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        opacity: 0; 
        transform: translateY(-50px) scale(0.9); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0) scale(1); 
    }
}

/* Botón de eliminar */
.btn-eliminar {
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-eliminar:hover {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    transform: scale(1.1);
}

.btn-outline-light {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.5);
    color: white;
    transition: all 0.3s ease;
}

.btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 5% auto;
        max-height: 95vh;
    }
    
    .modal-header {
        padding: 1rem 1.5rem;
    }
    
    .modal-header h3 {
        font-size: 1.25rem;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
}
</style>

<script>
// Funciones para los modales de confirmación
function confirmarEliminarResponsable(id, nombre) {
    document.getElementById('responsableId').value = id;
    document.getElementById('nombreResponsable').textContent = nombre;
    document.getElementById('modalEliminarResponsable').style.display = 'block';
}

function confirmarEliminarContacto(id, nombre) {
    document.getElementById('contactoId').value = id;
    document.getElementById('nombreContacto').textContent = nombre;
    document.getElementById('modalEliminarContacto').style.display = 'block';
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function openCambioCursoModal() {
    document.getElementById('modalCambioCurso').style.display = 'block';
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        // Si es el modal de editar información, usar la función específica
        if (event.target.id === 'editModal') {
            closeEditModal();
        } else {
            // Para otros modales, simplemente cerrar
            event.target.style.display = 'none';
        }
    }
}

// Cerrar modal con la tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modales = document.querySelectorAll('.modal');
        modales.forEach(modal => {
            if (modal.style.display === 'block') {
                // Si es el modal de editar información, usar la función específica
                if (modal.id === 'editModal') {
                    closeEditModal();
                } else {
                    // Para otros modales, simplemente cerrar
                    modal.style.display = 'none';
                }
            }
        });
    }
});

// Función para imprimir el boletín
function imprimirBoletin() {
    // Abrir la página de impresión en una nueva ventana
    window.open('imprimir_boletin.php?id=<?php echo $estudiante_id; ?>', '_blank', 'width=800,height=600');
}
</script>

<?php include 'includes/footer.php'; ?>
