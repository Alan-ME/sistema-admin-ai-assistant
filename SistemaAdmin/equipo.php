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

$pageTitle = 'Equipo Directivo (nueva arquitectura) - Sistema Administrativo E.E.S.T N°2';

// Verificar permisos (solo admin y directivo) - ANTES del header para evitar headers already sent
if (!($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'directivo')) {
    header('Location: index.php?error=unauthorized');
    exit();
}

// Procesar formulario de nuevo miembro - ANTES del header para evitar headers already sent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_miembro'])) {
    try {
        // Iniciar transacción
        $db->query("START TRANSACTION");
        
        // Insertar en equipo_directivo
        $sql = "INSERT INTO equipo_directivo (apellido, nombre, cargo, telefono, email, foto) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $_POST['apellido'],
            $_POST['nombre'],
            $_POST['cargo'],
            $_POST['telefono'] ?: null,
            $_POST['email'] ?: null,
            $_POST['foto'] ?: null
        ];
        
        $db->query($sql, $params);
        $equipo_id = $db->lastInsertId();
        
        // Si es preceptor, crear usuario automáticamente
        if ($_POST['cargo'] === 'preceptor') {
            // Buscar TODOS los usernames de preceptor existentes (activos e inactivos)
            $preceptores_existentes = $db->fetchAll("
                SELECT username 
                FROM usuarios 
                WHERE username LIKE 'preceptor%'
                ORDER BY username
            ");
            
            // Crear array de números ocupados
            $numeros_ocupados = [];
            foreach ($preceptores_existentes as $preceptor) {
                if (preg_match('/preceptor(\d+)/', $preceptor['username'], $matches)) {
                    $numeros_ocupados[] = intval($matches[1]);
                }
            }
            
            // Ordenar números ocupados para encontrar el primer hueco
            sort($numeros_ocupados);
            
            // Encontrar el primer número disponible
            $numero = 1;
            foreach ($numeros_ocupados as $numero_ocupado) {
                if ($numero < $numero_ocupado) {
                    // Encontramos un hueco, usar este número
                    break;
                }
                $numero = $numero_ocupado + 1;
            }
            
            // Si no hay huecos, usar el siguiente número después del más alto
            if (empty($numeros_ocupados)) {
                $numero = 1;
            }
            
            // Verificar que el username no exista
            $username = "preceptor" . $numero;
            $usuario_existente = $db->fetch("SELECT id FROM usuarios WHERE username = ?", [$username]);
            if ($usuario_existente) {
                throw new Exception("Error: El username {$username} ya existe. Intente nuevamente.");
            }
            
            $username = "preceptor" . $numero;
            $password_hash = password_hash('123456', PASSWORD_DEFAULT); // Contraseña por defecto
            
            // Insertar usuario
            $sql_usuario = "INSERT INTO usuarios (username, password, nombre, apellido, email, rol, activo) 
                           VALUES (?, ?, ?, ?, ?, 'preceptor', 1)";
            
            $params_usuario = [
                $username,
                $password_hash,
                $_POST['nombre'],
                $_POST['apellido'],
                $_POST['email'] ?: null
            ];
            
            $db->query($sql_usuario, $params_usuario);
            
            // Los mensajes de éxito se manejan después del redirect
        }
        
        // Confirmar transacción
        $db->query("COMMIT");
        
        // Redirigir para evitar duplicación en recargas (patrón POST-Redirect-GET)
        if ($_POST['cargo'] === 'preceptor') {
            header('Location: equipo.php?success=preceptor&username=' . urlencode($username));
        } else {
            header('Location: equipo.php?success=miembro');
        }
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $db->query("ROLLBACK");
        $error_message = "Error al registrar miembro: " . $e->getMessage();
    }
}

// Procesar eliminación de miembro - ANTES del header para evitar headers already sent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_miembro'])) {
    try {
        // Iniciar transacción
        $db->query("START TRANSACTION");
        
        // Obtener información del miembro antes de eliminar
        $miembro = $db->fetch("
            SELECT apellido, nombre, cargo FROM equipo_directivo 
            WHERE id = ?
        ", [$_POST['miembro_id']]);
        
        if (!$miembro) {
            throw new Exception('Miembro no encontrado');
        }
        
        // Verificar que no se elimine al admin
        if ($miembro['cargo'] === 'admin') {
            throw new Exception('No se puede eliminar al Administrador del equipo directivo');
        }
        
        // Eliminar miembro del equipo directivo (soft delete)
        $db->query("
            UPDATE equipo_directivo 
            SET activo = 0 
            WHERE id = ?
        ", [$_POST['miembro_id']]);
        
        // Si es preceptor, también desactivar el usuario correspondiente
        if ($miembro['cargo'] === 'preceptor') {
            // Buscar el usuario preceptor correspondiente (incluyendo inactivos)
            $usuario = $db->fetch("
                SELECT username FROM usuarios 
                WHERE nombre = ? AND apellido = ? AND rol = 'preceptor'
            ", [$miembro['nombre'], $miembro['apellido']]);
            
            if ($usuario) {
                // Desactivar el usuario
                $db->query("
                    UPDATE usuarios 
                    SET activo = 0 
                    WHERE username = ?
                ", [$usuario['username']]);
                
                // Eliminar usuarios preceptor inactivos para evitar conflictos
                $db->query("DELETE FROM usuarios WHERE username LIKE 'preceptor%' AND activo = 0");
                
                // Renumerar automáticamente los usuarios preceptor activos
                $usuarios_activos = $db->fetchAll("
                    SELECT id, username, nombre, apellido
                    FROM usuarios 
                    WHERE username LIKE 'preceptor%' AND activo = 1
                    ORDER BY fecha_creacion ASC
                ");
                
                // Renumerar directamente a los nombres finales
                $nuevo_numero = 1;
                foreach ($usuarios_activos as $usuario_activo) {
                    $nuevo_username = "preceptor" . $nuevo_numero;
                    $db->query("UPDATE usuarios SET username = ? WHERE id = ?", [$nuevo_username, $usuario_activo['id']]);
                    $nuevo_numero++;
                }
                
                // Mensaje específico para preceptores se maneja después del redirect
            }
        }
        
        // Confirmar transacción
        $db->query("COMMIT");
        
        // Redirigir para evitar duplicación en recargas (patrón POST-Redirect-GET)
        header('Location: equipo.php?success=eliminar&nombre=' . urlencode($miembro['apellido'] . ', ' . $miembro['nombre']));
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $db->query("ROLLBACK");
        $error_message = "Error al eliminar miembro: " . $e->getMessage();
    }
}

// Incluir header después de todo el procesamiento de formularios
include 'includes/header.php';

$action = $_GET['action'] ?? '';
$success_message = '';
$error_message = '';

// Manejar mensajes de éxito desde redirects (patrón POST-Redirect-GET)
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'preceptor':
            $username = $_GET['username'] ?? '';
            $success_message = "Preceptor registrado correctamente. Información de acceso: Usuario: {$username}, Contraseña: 123456";
            break;
        case 'miembro':
            $success_message = "Miembro del equipo directivo registrado correctamente";
            break;
        case 'eliminar':
            $nombre = $_GET['nombre'] ?? '';
            $success_message = "Miembro {$nombre} eliminado correctamente";
            break;
    }
}

// Obtener equipo directivo
$equipo = $db->fetchAll("
    SELECT * FROM equipo_directivo 
    WHERE activo = 1 
    ORDER BY 
        CASE cargo 
            WHEN 'admin' THEN 1
            WHEN 'directivo' THEN 2
            WHEN 'preceptor' THEN 3
            ELSE 4
        END,
        apellido, nombre,
        id ASC
");

$total_miembros = count($equipo);

$cargos_predefinidos = [
    'admin',
    'directivo',
    'preceptor'
];

// Header ya incluido al principio del archivo
?>

<section class="equipo-section">
    <div class="section-header">
        <h2>Equipo Directivo</h2>
        <a href="equipo.php?action=nuevo" class="btn btn-primary">
            <i class="fas fa-plus"></i> Agregar Miembro
        </a>
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
    


    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_miembros); ?></h3>
                <p>Total Miembros</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count(array_unique(array_column($equipo, 'cargo'))); ?></h3>
                <p>Cargos Diferentes</p>
            </div>
        </div>
    </div>

    <!-- Formulario nuevo miembro -->
    <?php if ($action === 'nuevo'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Agregar Miembro del Equipo Directivo</h3>
        </div>
        
        <!-- Mensaje de aclaración sobre contraseña -->
        <div class="alert alert-info" style="margin: 1rem;">
            <i class="fas fa-info-circle"></i>
            <strong>Información importante:</strong> Al crear un preceptor, se generará automáticamente un usuario con contraseña por defecto "123456". 
            El nombre de usuario será "preceptor" seguido de un número incremental (preceptor1, preceptor2, etc.).
        </div>
        
        <form method="POST" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="apellido">Apellido: *</label>
                    <input type="text" name="apellido" id="apellido" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="nombre">Nombre: *</label>
                    <input type="text" name="nombre" id="nombre" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="cargo">Cargo: *</label>
                    <select name="cargo" id="cargo" required>
                        <option value="">Seleccionar cargo</option>
                        <option value="preceptor">Preceptor</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefono">Teléfono:</label>
                    <input type="tel" name="telefono" id="telefono" maxlength="20" 
                           placeholder="Ej: 223-1234567">
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" maxlength="100" 
                           placeholder="Ej: director@eest2.edu.ar">
                </div>
                
                <div class="form-group">
                    <label for="foto">URL Foto:</label>
                    <input type="url" name="foto" id="foto" maxlength="255" 
                           placeholder="https://ejemplo.com/foto.jpg">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="guardar_miembro" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Miembro
                </button>
                <a href="equipo.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Lista del equipo -->
    <?php if (!empty($equipo)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Miembros del Equipo Directivo (<?php echo number_format($total_miembros); ?>)</h3>
        </div>
        <div class="card-body">
            <div class="equipo-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
                <?php foreach ($equipo as $miembro): ?>
                <div class="miembro-card" style="border: 1px solid var(--medium-gray); border-radius: var(--border-radius); padding: 1.5rem; background: white; box-shadow: var(--shadow); transition: transform 0.2s;">
                    <div class="miembro-header" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div class="miembro-foto" style="flex-shrink: 0;">
                            <?php if ($miembro['foto']): ?>
                                <img src="<?php echo htmlspecialchars($miembro['foto']); ?>" 
                                     alt="Foto de <?php echo htmlspecialchars($miembro['nombre']); ?>"
                                     style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                            <?php else: ?>
                                <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="miembro-info">
                            <h4 style="margin-bottom: 0.25rem; color: var(--text-color);">
                                <?php echo htmlspecialchars($miembro['apellido'] . ', ' . $miembro['nombre']); ?>
                            </h4>
                            <span class="status status-primary" style="font-size: 0.875rem;">
                                <?php echo htmlspecialchars($miembro['cargo']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="miembro-contacto" style="border-top: 1px solid var(--medium-gray); padding-top: 1rem;">
                        <?php if ($miembro['telefono']): ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            <i class="fas fa-phone" style="color: var(--secondary-color);"></i>
                            <span><?php echo htmlspecialchars($miembro['telefono']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($miembro['email']): ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                            <i class="fas fa-envelope" style="color: var(--secondary-color);"></i>
                            <a href="mailto:<?php echo htmlspecialchars($miembro['email']); ?>" 
                               style="color: var(--primary-color); text-decoration: none;">
                                <?php echo htmlspecialchars($miembro['email']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$miembro['telefono'] && !$miembro['email']): ?>
                        <small style="color: var(--secondary-color); font-style: italic;">
                            No hay información de contacto registrada
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Botón de eliminar -->
                    <?php if ($miembro['cargo'] !== 'Director'): ?>
                    <div class="miembro-actions" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--medium-gray);">
                        <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar a <?php echo htmlspecialchars($miembro['apellido'] . ', ' . $miembro['nombre']); ?> del equipo directivo?');" style="margin: 0;">
                            <input type="hidden" name="miembro_id" value="<?php echo $miembro['id']; ?>">
                            <button type="submit" name="eliminar_miembro" class="btn btn-danger btn-sm" style="width: 100%;">
                                <i class="fas fa-trash"></i> Eliminar Miembro
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="miembro-actions" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--medium-gray);">
                        <small style="color: var(--secondary-color); font-style: italic; display: block; text-align: center;">
                            <i class="fas fa-shield-alt"></i> Director - No se puede eliminar
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Vista de tabla -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Vista de Lista</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Apellido y Nombre</th>
                        <th>Cargo</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipo as $miembro): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <?php if ($miembro['foto']): ?>
                                    <img src="<?php echo htmlspecialchars($miembro['foto']); ?>" 
                                         alt="Foto" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($miembro['apellido'] . ', ' . $miembro['nombre']); ?></strong>
                            </div>
                        </td>
                        <td>
                            <span class="status status-primary">
                                <?php echo htmlspecialchars($miembro['cargo']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($miembro['telefono']): ?>
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($miembro['telefono']); ?>
                            <?php else: ?>
                                <span style="color: var(--secondary-color);">No registrado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($miembro['email']): ?>
                                <a href="mailto:<?php echo htmlspecialchars($miembro['email']); ?>" 
                                   style="color: var(--primary-color);">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($miembro['email']); ?>
                                </a>
                            <?php else: ?>
                                <span style="color: var(--secondary-color);">No registrado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status status-success">Activo</span>
                        </td>
                        <td>
                            <?php if ($miembro['cargo'] !== 'Director'): ?>
                            <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar a <?php echo htmlspecialchars($miembro['apellido'] . ', ' . $miembro['nombre']); ?> del equipo directivo?');" style="margin: 0;">
                                <input type="hidden" name="miembro_id" value="<?php echo $miembro['id']; ?>">
                                <button type="submit" name="eliminar_miembro" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </form>
                            <?php else: ?>
                            <small style="color: var(--secondary-color); font-style: italic;">
                                <i class="fas fa-shield-alt"></i> No se puede eliminar
                            </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Estado vacío -->
    <div class="card">
        <div class="card-body text-center" style="padding: 3rem;">
            <i class="fas fa-users" style="font-size: 4rem; color: var(--secondary-color); opacity: 0.3; margin-bottom: 1rem;"></i>
            <h3 style="color: var(--secondary-color); margin-bottom: 0.5rem;">No hay miembros registrados</h3>
            <p style="color: var(--secondary-color); margin-bottom: 2rem;">
                Comienza agregando los miembros del equipo directivo de la institución
            </p>
            <a href="equipo.php?action=nuevo" class="btn btn-primary">
                <i class="fas fa-plus"></i> Agregar Primer Miembro
            </a>
        </div>
    </div>
    <?php endif; ?>
</section>

<style>
.miembro-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

@media (max-width: 768px) {
    .equipo-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
