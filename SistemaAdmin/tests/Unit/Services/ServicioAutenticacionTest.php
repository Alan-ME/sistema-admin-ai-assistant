<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use SistemaAdmin\Services\ServicioAutenticacion;

/**
 * Tests unitarios para ServicioAutenticacion
 */
class ServicioAutenticacionTest extends TestCase
{
    private MockObject $databaseMock;
    private ServicioAutenticacion $servicio;

    protected function setUp(): void
    {
        // Crear mock de la base de datos
        $this->databaseMock = $this->createMock(\PDO::class);
        
        // Crear servicio con mock
        $this->servicio = new ServicioAutenticacion($this->databaseMock);
    }

    /**
     * Test: Autenticación exitosa
     */
    public function testAutenticacionExitosa()
    {
        // Datos de usuario mock
        $usuarioMock = [
            'id' => 1,
            'username' => 'admin',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'email' => 'admin@eest2.edu.ar',
            'rol' => 'admin',
            'activo' => 1
        ];

        // Mock del statement
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetch')->willReturn($usuarioMock);

        // Mock de la base de datos
        $this->databaseMock->method('fetch')->willReturn($usuarioMock);

        $resultado = $this->servicio->autenticar('admin', 'password');

        $this->assertTrue($resultado['success']);
        $this->assertEquals(1, $resultado['data']['id']);
        $this->assertEquals('admin', $resultado['data']['username']);
        $this->assertEquals('Administrador', $resultado['data']['nombre']);
        $this->assertEquals('admin', $resultado['data']['rol']);
    }

    /**
     * Test: Usuario no encontrado
     */
    public function testUsuarioNoEncontrado()
    {
        // Mock de la base de datos que retorna null
        $this->databaseMock->method('fetch')->willReturn(null);

        $resultado = $this->servicio->autenticar('usuario_inexistente', 'password');

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Usuario o contraseña incorrectos', $resultado['error']);
    }

    /**
     * Test: Contraseña incorrecta
     */
    public function testContrasenaIncorrecta()
    {
        // Datos de usuario mock
        $usuarioMock = [
            'id' => 1,
            'username' => 'admin',
            'password' => password_hash('password_correcta', PASSWORD_DEFAULT),
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'email' => 'admin@eest2.edu.ar',
            'rol' => 'admin',
            'activo' => 1
        ];

        // Mock de la base de datos
        $this->databaseMock->method('fetch')->willReturn($usuarioMock);

        $resultado = $this->servicio->autenticar('admin', 'password_incorrecta');

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Usuario o contraseña incorrectos', $resultado['error']);
    }

    /**
     * Test: Usuario inactivo
     */
    public function testUsuarioInactivo()
    {
        // Datos de usuario inactivo
        $usuarioInactivo = [
            'id' => 1,
            'username' => 'admin',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'email' => 'admin@eest2.edu.ar',
            'rol' => 'admin',
            'activo' => 0
        ];

        // Mock de la base de datos que retorna usuario inactivo
        $this->databaseMock->method('fetch')->willReturn($usuarioInactivo);

        $resultado = $this->servicio->autenticar('admin', 'password');

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Usuario o contraseña incorrectos', $resultado['error']);
    }

    /**
     * Test: Error de base de datos
     */
    public function testErrorBaseDatos()
    {
        // Mock que lanza excepción
        $this->databaseMock->method('fetch')->willThrowException(new \Exception('Error de conexión'));

        $resultado = $this->servicio->autenticar('admin', 'password');

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Error interno del servidor', $resultado['error']);
    }

    /**
     * Test: Verificar sesión activa
     */
    public function testVerificarSesionActiva()
    {
        // Simular sesión activa
        $_SESSION['usuario_id'] = 1;

        // Datos de usuario mock
        $usuarioMock = [
            'id' => 1,
            'username' => 'admin',
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'email' => 'admin@eest2.edu.ar',
            'rol' => 'admin'
        ];

        // Mock de la base de datos
        $this->databaseMock->method('fetch')->willReturn($usuarioMock);

        $usuario = $this->servicio->verificarSesion();

        $this->assertNotNull($usuario);
        $this->assertEquals(1, $usuario['id']);
        $this->assertEquals('admin', $usuario['username']);
    }

    /**
     * Test: Verificar sesión inactiva
     */
    public function testVerificarSesionInactiva()
    {
        // Limpiar sesión
        unset($_SESSION['usuario_id']);

        $usuario = $this->servicio->verificarSesion();

        $this->assertNull($usuario);
    }

    /**
     * Test: Tiene permiso - admin con permiso universal
     */
    public function testTienePermisoAdmin()
    {
        // Simular sesión de admin
        $_SESSION['usuario_id'] = 1;
        $_SESSION['rol'] = 'admin';

        // Datos de usuario admin
        $usuarioMock = [
            'id' => 1,
            'username' => 'admin',
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'email' => 'admin@eest2.edu.ar',
            'rol' => 'admin'
        ];

        // Mock de la base de datos
        $this->databaseMock->method('fetch')->willReturn($usuarioMock);

        $tienePermiso = $this->servicio->tienePermiso('cualquier_permiso');

        $this->assertTrue($tienePermiso);
    }

    /**
     * Test: Tiene permiso - profesor con permiso específico
     */
    public function testTienePermisoProfesor()
    {
        // Simular sesión de profesor
        $_SESSION['usuario_id'] = 2;
        $_SESSION['rol'] = 'profesor';

        // Datos de usuario profesor
        $usuarioMock = [
            'id' => 2,
            'username' => 'profesor1',
            'nombre' => 'Profesor',
            'apellido' => 'Test',
            'email' => 'profesor@eest2.edu.ar',
            'rol' => 'profesor'
        ];

        // Mock de la base de datos
        $this->databaseMock->method('fetch')->willReturn($usuarioMock);

        $tienePermiso = $this->servicio->tienePermiso('ver_estudiantes');
        $this->assertTrue($tienePermiso);

        $noTienePermiso = $this->servicio->tienePermiso('gestionar_usuarios');
        $this->assertFalse($noTienePermiso);
    }

    /**
     * Test: Tiene permiso - rol inexistente
     */
    public function testTienePermisoRolInexistente()
    {
        // Simular sesión con rol inexistente
        $_SESSION['usuario_id'] = 3;
        $_SESSION['rol'] = 'rol_inexistente';

        // Datos de usuario con rol inexistente
        $usuarioMock = [
            'id' => 3,
            'username' => 'usuario_test',
            'nombre' => 'Usuario',
            'apellido' => 'Test',
            'email' => 'usuario@eest2.edu.ar',
            'rol' => 'rol_inexistente'
        ];

        // Mock de la base de datos
        $this->databaseMock->method('fetch')->willReturn($usuarioMock);

        $tienePermiso = $this->servicio->tienePermiso('cualquier_permiso');

        $this->assertFalse($tienePermiso);
    }

    /**
     * Test: Cambiar contraseña exitoso
     */
    public function testCambiarPasswordExitoso()
    {
        $usuarioId = 1;
        $passwordActual = 'password_actual';
        $passwordNuevo = 'password_nuevo';

        // Mock de usuario con contraseña actual
        $usuarioMock = [
            'password' => password_hash($passwordActual, PASSWORD_DEFAULT)
        ];

        // Mock del statement para UPDATE
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('rowCount')->willReturn(1);

        // Mock de la base de datos
        $this->databaseMock->method('fetch')->willReturn($usuarioMock);
        $this->databaseMock->method('query')->willReturn($stmtMock);

        $resultado = $this->servicio->cambiarPassword($usuarioId, $passwordActual, $passwordNuevo);

        $this->assertTrue($resultado['success']);
        $this->assertEquals('Contraseña actualizada exitosamente', $resultado['message']);
    }

    /**
     * Test: Cambiar contraseña - contraseña actual incorrecta
     */
    public function testCambiarPasswordActualIncorrecta()
    {
        $usuarioId = 1;
        $passwordActual = 'password_incorrecta';
        $passwordNuevo = 'password_nuevo';

        // Mock de usuario con contraseña diferente
        $usuarioMock = [
            'password' => password_hash('password_correcta', PASSWORD_DEFAULT)
        ];

        // Mock de la base de datos
        $this->databaseMock->method('fetch')->willReturn($usuarioMock);

        $resultado = $this->servicio->cambiarPassword($usuarioId, $passwordActual, $passwordNuevo);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('La contraseña actual es incorrecta', $resultado['error']);
    }

    protected function tearDown(): void
    {
        // Limpiar sesión después de cada test
        $_SESSION = [];
    }
}
