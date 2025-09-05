<?php
/**
 * Bootstrap para tests
 * 
 * Configura el entorno de testing y carga las dependencias necesarias
 */

// Configurar entorno de testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar autoloader
require_once __DIR__ . '/../src/autoload.php';
require_once __DIR__ . '/../config/database.php';

// Configurar base de datos de testing
if (getenv('DB_NAME') === 'sistema_admin_eest2_test') {
    // Crear base de datos de testing si no existe
    try {
        $pdo = new PDO(
            "mysql:host=" . getenv('DB_HOST') . ";charset=utf8mb4",
            getenv('DB_USER'),
            getenv('DB_PASS'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        // Crear base de datos de testing
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . getenv('DB_NAME'));
        $pdo->exec("USE " . getenv('DB_NAME'));
        
        // Ejecutar script de creación de tablas
        $sql = file_get_contents(__DIR__ . '/../database/sistema_admin_eest2.sql');
        $pdo->exec($sql);
        
    } catch (PDOException $e) {
        echo "Error configurando base de datos de testing: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Configurar sesión para testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar sesión para cada test
$_SESSION = [];
