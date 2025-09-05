<?php

/**
 * Autoloader para la nueva arquitectura del Sistema SIGE
 * 
 * Este archivo permite cargar automáticamente las clases de la nueva arquitectura
 * sin afectar el sistema actual.
 */

spl_autoload_register(function ($className) {
    // Solo procesar clases del namespace SistemaAdmin
    if (strpos($className, 'SistemaAdmin\\') !== 0) {
        return;
    }
    
    // Convertir namespace a ruta de archivo
    $relativePath = str_replace('SistemaAdmin\\', '', $className);
    $relativePath = str_replace('\\', '/', $relativePath);
    
    // Construir la ruta completa
    $filePath = __DIR__ . '/' . $relativePath . '.php';
    
    // Cargar el archivo si existe
    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

// Función helper para incluir el autoloader en archivos existentes
function cargarNuevaArquitectura() {
    require_once __DIR__ . '/autoload.php';
}
