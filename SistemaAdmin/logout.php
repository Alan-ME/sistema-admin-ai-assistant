<?php
// Iniciar sesión
session_start();

// Incluir la nueva arquitectura
require_once 'src/autoload.php';
require_once 'config/database.php';

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

// Cerrar sesión
$resultado = $loginController->cerrarSesion();

// Redirigir al login
header('Location: login.php');
exit();
?>