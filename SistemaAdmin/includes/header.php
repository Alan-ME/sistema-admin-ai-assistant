<?php
// Este header funciona con la nueva arquitectura de autenticación
// pero mantiene el diseño original
$currentUser = [
    'nombre' => $_SESSION['nombre'] ?? 'Usuario',
    'apellido' => $_SESSION['apellido'] ?? '',
    'rol' => $_SESSION['rol'] ?? 'usuario'
];
$currentPage = basename($_SERVER['PHP_SELF']);

// Función para verificar permisos (compatible con la nueva arquitectura)
function hasRole($roles) {
    $userRole = $_SESSION['rol'] ?? '';
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    return $userRole === $roles;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Sistema Administrativo E.E.S.T N°2'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="header-top">
            <div class="logo-section">
                <img src="img/logo-eest2.png" alt="Logo EEST N°2" class="logo">
                <div class="school-info">
                    <h1 class="brand-title">Sistema <span>Administrativo</span></h1>
                    <h2>E.E.S.T. N°2 "Educación y Trabajo"</h2>
                </div>
            </div>
            <button class="hamburger" id="hamburger-menu" aria-label="Abrir menú" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="user-section">
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($currentUser['nombre'] . ' ' . $currentUser['apellido']); ?></span>
                    <span class="role">(<?php echo ucfirst($currentUser['rol']); ?>)</span>
                </div>
                <a href="logout.php" class="logout-btn" title="Cerrar sesión">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="app-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-school"></i>
                <span>Escuela</span>
            </div>
            <nav class="sidebar-nav">
                <ul class="menu">
                    <li><a href="index.php" class="menu-link <?php echo $currentPage==='index.php'?'active':''; ?>"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                    <li><a href="estudiantes.php" class="menu-link <?php echo $currentPage==='estudiantes.php'?'active':''; ?>"><i class="fas fa-users"></i><span>Estudiantes</span></a></li>
                    <li><a href="profesores.php" class="menu-link <?php echo $currentPage==='profesores.php'?'active':''; ?>"><i class="fas fa-chalkboard-teacher"></i><span>Profesores</span></a></li>
                    <li><a href="cursos.php" class="menu-link <?php echo $currentPage==='cursos.php'?'active':''; ?>"><i class="fas fa-graduation-cap"></i><span>Cursos</span></a></li>
                    <li><a href="horarios.php" class="menu-link <?php echo $currentPage==='horarios.php'?'active':''; ?>"><i class="fas fa-clock"></i><span>Horarios</span></a></li>
                    <li><a href="llamados.php" class="menu-link <?php echo $currentPage==='llamados.php'?'active':''; ?>"><i class="fas fa-exclamation-triangle"></i><span>Llamados</span></a></li>
                    <?php if (hasRole(['admin', 'directivo'])): ?>
                    <li class="menu-section">Académico</li>
                    <li><a href="notas.php" class="menu-link <?php echo $currentPage==='notas.php'?'active':''; ?>"><i class="fas fa-clipboard-check"></i><span>Notas</span></a></li>
                    <li><a href="materias.php" class="menu-link <?php echo $currentPage==='materias.php'?'active':''; ?>"><i class="fas fa-book"></i><span>Materias</span></a></li>
                    <li><a href="materias_previas.php" class="menu-link <?php echo $currentPage==='materias_previas.php'?'active':''; ?>"><i class="fas fa-bookmark"></i><span>Materias Previas</span></a></li>
                    <li><a href="especialidades.php" class="menu-link <?php echo $currentPage==='especialidades.php'?'active':''; ?>"><i class="fas fa-sitemap"></i><span>Especialidades</span></a></li>

                    <li><a href="equipo.php" class="menu-link <?php echo $currentPage==='equipo.php'?'active':''; ?>"><i class="fas fa-user-tie"></i><span>Equipo Directivo</span></a></li>
                    <?php endif; ?>

                    <li><a href="logout.php" class="menu-link"><i class="fas fa-sign-out-alt"></i><span>Salir</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="breadcrumb-bar">
                <div class="crumbs-left">
                    <i class="fas fa-home"></i>
                    <a href="index.php">Inicio</a>
                    <span>/</span>
                    <strong><?php echo htmlspecialchars($pageTitle ?? ''); ?></strong>
                </div>
                <div class="crumbs-right">
                    <i class="far fa-clock"></i>
                    <span id="clock-time"></span>
                    <i class="far fa-calendar-alt" style="margin-left:.75rem;"></i>
                    <span id="clock-date"></span>
                </div>
            </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var hamburger = document.getElementById('hamburger-menu');
            var sidebar = document.getElementById('sidebar');
            if (hamburger && sidebar) {
                hamburger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('open');
                    hamburger.setAttribute('aria-expanded', sidebar.classList.contains('open') ? 'true' : 'false');
                });
                document.addEventListener('click', function(e) {
                    if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                        sidebar.classList.remove('open');
                        hamburger.setAttribute('aria-expanded', 'false');
                    }
                    if (e.target.classList.contains('menu-link')) {
                        sidebar.classList.remove('open');
                        hamburger.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            // Reloj local (hora del sistema del usuario)
            function updateClock() {
                try {
                    var now = new Date();
                    var time = new Intl.DateTimeFormat('es-AR', { hour: '2-digit', minute: '2-digit' }).format(now);
                    var date = new Intl.DateTimeFormat('es-AR', { day: '2-digit', month: 'short', year: 'numeric' }).format(now);
                    var tEl = document.getElementById('clock-time');
                    var dEl = document.getElementById('clock-date');
                    if (tEl) tEl.textContent = time;
                    if (dEl) dEl.textContent = date;
                } catch (e) {}
            }
            updateClock();
            setInterval(updateClock, 60000);
        });
        </script>

<?php
// Función global para obtener token CSRF
function getCSRFToken() {
    if (!isset($GLOBALS['servicioSeguridad'])) {
        require_once 'src/autoload.php';
        require_once 'config/database.php';
        $db = Database::getInstance();
        $GLOBALS['servicioSeguridad'] = new SistemaAdmin\Services\ServicioSeguridad($db);
    }
    return $GLOBALS['servicioSeguridad']->generarTokenCSRF();
}

// Función global para verificar token CSRF
function verifyCSRFToken($token) {
    if (!isset($GLOBALS['servicioSeguridad'])) {
        require_once 'src/autoload.php';
        require_once 'config/database.php';
        $db = Database::getInstance();
        $GLOBALS['servicioSeguridad'] = new SistemaAdmin\Services\ServicioSeguridad($db);
    }
    return $GLOBALS['servicioSeguridad']->verificarTokenCSRF($token);
}
?>