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

$pageTitle = 'Dashboard (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';
include 'includes/header.php';
require_once 'includes/character_encoding.php';

use SistemaAdmin\Services\ServicioEstudiantes;
use SistemaAdmin\Services\ServicioProfesores;
use SistemaAdmin\Services\ServicioLlamados;
use SistemaAdmin\Controllers\DashboardController;
use SistemaAdmin\Mappers\EstudianteMapper;
use SistemaAdmin\Mappers\ProfesorMapper;
use SistemaAdmin\Mappers\LlamadoMapper;

// Inicializar servicios
$db = Database::getInstance();
$servicioAutenticacion = new ServicioAutenticacion($db);
$estudianteMapper = new EstudianteMapper($db);
$profesorMapper = new ProfesorMapper($db);
$llamadoMapper = new LlamadoMapper($db);
$servicioEstudiantes = new ServicioEstudiantes($estudianteMapper);
$servicioProfesores = new ServicioProfesores($profesorMapper);
$servicioLlamados = new ServicioLlamados($llamadoMapper, $estudianteMapper);
$dashboardController = new DashboardController($servicioEstudiantes, $servicioProfesores, $servicioLlamados, $servicioAutenticacion, $db);

// Obtener resumen del dashboard usando la nueva arquitectura
$resumen = $dashboardController->obtenerResumen();

// Extraer datos del resumen
$estadisticas = $resumen['data']['estadisticas'] ?? [];
$cumpleaneros = $resumen['data']['cumpleaneros'] ?? [];
$ultimos_llamados = $resumen['data']['ultimos_llamados'] ?? [];
$estudiantes_por_turno = $resumen['data']['estudiantes_por_turno'] ?? [];

// Preparar estadísticas para compatibilidad con el HTML existente
$stats = [
    'total_estudiantes' => $estadisticas['estudiantes']['total_estudiantes'] ?? 0,
    'total_cursos' => $estadisticas['adicionales']['total_cursos'] ?? 0,
    'total_profesores' => $estadisticas['profesores']['total_profesores'] ?? 0,
    'cumpleanos_hoy' => $estadisticas['adicionales']['cumpleanos_hoy'] ?? 0
];
?>

<section class="dashboard-section">
    <div class="section-header">
        <h2>Panel de Control</h2>
        <div class="header-info">
            <span class="current-date">
                <i class="fas fa-calendar"></i>
                <?php echo strftime('%A, %d de %B de %Y', time()); ?>
            </span>
        </div>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            No tienes permisos para acceder a esa sección.
        </div>
    <?php endif; ?>

    <!-- Estadísticas principales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_estudiantes']); ?></h3>
                <p>Estudiantes Activos</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_cursos']); ?></h3>
                <p>Cursos Activos</p>
            </div>
        </div>
        

        
        <div class="stat-card">
            <div class="stat-icon" style="background: #8b5cf6;">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_profesores']); ?></h3>
                <p>Profesores Activos</p>
            </div>
        </div>
        
        <?php if ($stats['cumpleanos_hoy'] > 0): ?>
        <div class="stat-card">
            <div class="stat-icon" style="background: #8b5cf6;">
                <i class="fas fa-birthday-cake"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['cumpleanos_hoy']); ?></h3>
                <p>Cumpleaños Hoy</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Acciones rápidas -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Acciones Rápidas</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions">
                <a href="estudiantes.php?action=nuevo" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <strong>Agregar Estudiante</strong>
                        <br><small>Registrar nuevo alumno</small>
                    </div>
                </a>
                <?php if (hasRole('admin') || hasRole('directivo')): ?>
                <a href="notas.php?action=nueva" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <strong>Cargar Nota</strong>
                        <br><small>Registrar calificación</small>
                    </div>
                </a>
                <?php endif; ?>
                <a href="llamados.php?action=nuevo" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <strong>Nuevo Llamado</strong>
                        <br><small>Registrar llamado de atención</small>
                    </div>
                </a>
                <?php if (hasRole('admin') || hasRole('directivo')): ?>
                <a href="horarios.php" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <strong>Gestionar Horarios</strong>
                        <br><small>Configurar horarios de clases</small>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Estudiantes por turno -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Estudiantes por Turno</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($estudiantes_por_turno)): ?>
                    <?php foreach ($estudiantes_por_turno as $turno): ?>
                    <div class="turno-item" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--medium-gray);">
                        <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($turno['turno']); ?></span>
                        <span class="status status-success"><?php echo number_format($turno['cantidad']); ?> estudiantes</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center" style="color: var(--secondary-color); padding: 2rem;">No hay datos de turnos disponibles</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Últimos llamados -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Últimos Llamados de Atención</h3>
                <a href="llamados.php" class="btn btn-sm btn-secondary" style="margin-left: 1rem;">Ver todos</a>
            </div>
            <div class="card-body">
                <?php if (!empty($ultimos_llamados)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Estudiante</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_llamados as $llamado): ?>
                            <tr>
                                <td><?php echo date('d/m', strtotime($llamado['fecha'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($llamado['apellido'] . ', ' . $llamado['nombre']); ?></strong>
                                    <br><small><?php echo $llamado['anio'] . '° ' . $llamado['division']; ?></small>
                                </td>
                                <td>
                                    <span class="status status-warning">
                                        <?php echo htmlspecialchars(substr($llamado['motivo'], 0, 50), ENT_QUOTES, 'UTF-8') . (strlen($llamado['motivo']) > 50 ? '...' : ''); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-center" style="color: var(--secondary-color); padding: 2rem;">No hay llamados recientes</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cumpleañeros de hoy (solo si hay cumpleañeros) -->
    <?php if (!empty($cumpleaneros)): ?>
    <div class="grid" style="grid-template-columns: 1fr; gap: 2rem; margin-top: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-birthday-cake"></i> Cumpleaños de Hoy
                </h3>
            </div>
            <div class="card-body">
                <?php foreach ($cumpleaneros as $cumpleanero): ?>
                <div class="cumpleanero-item" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--medium-gray);">
                    <div>
                        <strong><?php echo htmlspecialchars($cumpleanero['apellido'] . ', ' . $cumpleanero['nombre']); ?></strong>
                        <br><small><?php echo $cumpleanero['anio'] . '° ' . $cumpleanero['division'] . ' - ' . $cumpleanero['especialidad']; ?></small>
                    </div>
                    <span class="status" style="background: #8b5cf6; color: white;"><?php echo $cumpleanero['edad']; ?> años</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</section>

<style>
.current-date {
    color: var(--secondary-color);
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-info {
    display: flex;
    align-items: center;
}

@media (max-width: 768px) {
    .grid {
        grid-template-columns: 1fr !important;
    }
    
    .header-info {
        margin-top: 1rem;
    }
}
</style>

<?php 
// Configurar locale para fechas en español
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'Spanish');
include 'includes/footer.php'; 
?>
