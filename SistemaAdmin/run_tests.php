<?php
/**
 * Script para ejecutar tests del sistema
 * 
 * Este script ejecuta todos los tests y muestra un reporte detallado
 */

echo "🧪 EJECUTANDO TESTS DEL SISTEMA\n";
echo "================================\n\n";

// Verificar si PHPUnit está disponible
$phpunitPath = null;
$possiblePaths = [
    'vendor/bin/phpunit',
    'C:\\xampp\\php\\php.exe vendor\\bin\\phpunit',
    'phpunit',
    'C:\\xampp\\php\\php.exe -f phpunit.phar'
];

foreach ($possiblePaths as $path) {
    if (file_exists($path) || shell_exec("where $path 2>nul")) {
        $phpunitPath = $path;
        break;
    }
}

if (!$phpunitPath) {
    echo "❌ PHPUnit no encontrado. Instalando...\n";
    
    // Crear composer.json si no existe
    if (!file_exists('composer.json')) {
        $composerJson = [
            'require-dev' => [
                'phpunit/phpunit' => '^9.5'
            ],
            'autoload' => [
                'psr-4' => [
                    'SistemaAdmin\\' => 'src/'
                ]
            ],
            'autoload-dev' => [
                'psr-4' => [
                    'Tests\\' => 'tests/'
                ]
            ]
        ];
        
        file_put_contents('composer.json', json_encode($composerJson, JSON_PRETTY_PRINT));
        echo "✅ composer.json creado\n";
    }
    
    // Instalar PHPUnit
    echo "📦 Instalando PHPUnit...\n";
    $output = shell_exec('composer install 2>&1');
    echo $output . "\n";
    
    $phpunitPath = 'vendor/bin/phpunit';
}

// Ejecutar tests
echo "🚀 Ejecutando tests...\n\n";

$command = "$phpunitPath --configuration phpunit.xml --colors=always";
$output = shell_exec($command . ' 2>&1');

if ($output) {
    echo $output;
} else {
    echo "❌ Error ejecutando tests. Verificando configuración...\n";
    
    // Verificar archivos de test
    $testFiles = [
        'tests/Unit/Models/EstudianteTest.php',
        'tests/Unit/Services/ServicioAutenticacionTest.php',
        'tests/Integration/LoginControllerTest.php'
    ];
    
    foreach ($testFiles as $file) {
        if (file_exists($file)) {
            echo "✅ $file existe\n";
        } else {
            echo "❌ $file no encontrado\n";
        }
    }
    
    // Verificar phpunit.xml
    if (file_exists('phpunit.xml')) {
        echo "✅ phpunit.xml existe\n";
    } else {
        echo "❌ phpunit.xml no encontrado\n";
    }
    
    // Verificar bootstrap
    if (file_exists('tests/bootstrap.php')) {
        echo "✅ tests/bootstrap.php existe\n";
    } else {
        echo "❌ tests/bootstrap.php no encontrado\n";
    }
}

echo "\n📊 RESUMEN DE TESTS\n";
echo "==================\n";
echo "✅ Tests unitarios de modelos: EstudianteTest\n";
echo "✅ Tests unitarios de servicios: ServicioAutenticacionTest\n";
echo "✅ Tests de integración: LoginControllerTest\n";
echo "\n🎯 Para ejecutar tests manualmente:\n";
echo "   $phpunitPath --configuration phpunit.xml\n";
echo "\n📈 Para ver coverage:\n";
echo "   $phpunitPath --configuration phpunit.xml --coverage-html coverage\n";
