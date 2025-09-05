<?php
// Iniciar sesión al principio
session_start();

// Incluir la nueva arquitectura
require_once 'src/autoload.php';
require_once 'config/database.php';
require_once 'includes/csrf_functions.php';

use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioSeguridad;
use SistemaAdmin\Services\ServicioLogging;
use SistemaAdmin\Controllers\LoginController;

// Inicializar servicios
$db = Database::getInstance();
$servicioAutenticacion = new ServicioAutenticacion($db);
$servicioSeguridad = new ServicioSeguridad($db);
$servicioLogging = new ServicioLogging($db);
$loginController = new LoginController($servicioAutenticacion, $servicioSeguridad, $servicioLogging);

// Crear tablas de logs si no existen
$servicioLogging->crearTablasLogs();

// Configurar headers de seguridad
$servicioSeguridad->configurarHeadersSeguridad();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Usar el nuevo controller para autenticación
    $resultado = $loginController->autenticar([
        'username' => $username,
        'password' => $password,
        'csrf_token' => $_POST['csrf_token'] ?? ''
    ]);

    if ($resultado['success']) {
        header('Location: index.php');
        exit();
    } else {
        $error = $resultado['error'] ?? 'Error de autenticación';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="img/logo-eest2.png" alt="Logo EEST N°2" class="logo">
                <h1>Sistema Administrativo</h1>
                <h2>E.E.S.T. N°2 "Educación y Trabajo"</h2>
                <p class="login-motto">Transformando la educación a través de la tecnología</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user-graduate"></i> Usuario:
                    </label>
                    <input type="text" id="username" name="username" required placeholder="Ingresa tu nombre de usuario">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Contraseña:
                    </label>
                    <input type="password" id="password" name="password" required placeholder="Ingresa tu contraseña">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-graduation-cap"></i> Acceder al Sistema Educativo
                </button>
            </form>
            
            <div class="login-footer">
                <p><i class="fas fa-graduation-cap"></i> Sistema Integral de Gestión Educativa</p>
                <small>E.E.S.T. N°2 "Educación y Trabajo" - Formando Futuros Profesionales - <?php echo date('Y'); ?></small>
            </div>
        </div>
    </div>
</body>
</html>
