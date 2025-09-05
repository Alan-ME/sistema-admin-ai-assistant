<?php
/**
 * Funciones CSRF para el sistema
 * Este archivo contiene solo las funciones necesarias para CSRF
 * sin generar HTML
 */

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
