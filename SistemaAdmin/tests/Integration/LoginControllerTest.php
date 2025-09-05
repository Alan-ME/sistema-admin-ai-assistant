<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use SistemaAdmin\Controllers\LoginController;
use SistemaAdmin\Services\ServicioAutenticacion;
use SistemaAdmin\Services\ServicioSeguridad;
use SistemaAdmin\Services\ServicioLogging;

/**
 * Tests de integración para LoginController
 */
class LoginControllerTest extends TestCase
{
    private MockObject $servicioAutenticacionMock;
    private MockObject $servicioSeguridadMock;
    private MockObject $servicioLoggingMock;
    private LoginController $controller;

    protected function setUp(): void
    {
        // Crear mocks de los servicios
        $this->servicioAutenticacionMock = $this->createMock(ServicioAutenticacion::class);
        $this->servicioSeguridadMock = $this->createMock(ServicioSeguridad::class);
        $this->servicioLoggingMock = $this->createMock(ServicioLogging::class);
        
        // Crear controller con mocks
        $this->controller = new LoginController(
            $this->servicioAutenticacionMock,
            $this->servicioSeguridadMock,
            $this->servicioLoggingMock
        );
    }

    /**
     * Test: Autenticación exitosa
     */
    public function testAutenticacionExitosa()
    {
        $datos = [
            'username' => 'admin',
            'password' => 'password'
        ];

        // Mock del servicio de seguridad para rate limiting
        $this->servicioSeguridadMock
            ->expects($this->once())
            ->method('obtenerIPCliente')
            ->willReturn('192.168.1.1');

        $this->servicioSeguridadMock
            ->expects($this->once())
            ->method('verificarRateLimit')
            ->with('192.168.1.1', 'login')
            ->willReturn(['allowed' => true]);

        // Mock del servicio que retorna éxito
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('autenticar')
            ->with('admin', 'password')
            ->willReturn([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'username' => 'admin',
                    'nombre' => 'Administrador',
                    'apellido' => 'Sistema',
                    'email' => 'admin@eest2.edu.ar',
                    'rol' => 'admin'
                ]
            ]);

        // Mock del método actualizarUltimoAcceso
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('actualizarUltimoAcceso')
            ->with(1)
            ->willReturn(true);

        // Mock del servicio de logging
        $this->servicioLoggingMock
            ->expects($this->once())
            ->method('registrarEventoSeguridad')
            ->with('LOGIN_SUCCESS', 'Login exitoso', ['username' => 'admin', 'user_id' => 1]);

        $resultado = $this->controller->autenticar($datos);

        $this->assertTrue($resultado['success']);
        $this->assertEquals('Autenticación exitosa', $resultado['message']);
        $this->assertEquals(1, $resultado['data']['id']);
        $this->assertEquals('admin', $resultado['data']['username']);
    }

    /**
     * Test: Autenticación fallida
     */
    public function testAutenticacionFallida()
    {
        $datos = [
            'username' => 'admin',
            'password' => 'password_incorrecta'
        ];

        // Mock del servicio que retorna error
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('autenticar')
            ->with('admin', 'password_incorrecta')
            ->willReturn([
                'success' => false,
                'error' => 'Usuario o contraseña incorrectos'
            ]);

        $resultado = $this->controller->autenticar($datos);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Usuario o contraseña incorrectos', $resultado['error']);
    }

    /**
     * Test: Validación de datos faltantes
     */
    public function testValidacionDatosFaltantes()
    {
        $datos = [
            'username' => '',
            'password' => 'password'
        ];

        $resultado = $this->controller->autenticar($datos);

        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('errors', $resultado);
        $this->assertContains('El nombre de usuario es requerido', $resultado['errors']);
    }

    /**
     * Test: Validación de contraseña faltante
     */
    public function testValidacionPasswordFaltante()
    {
        $datos = [
            'username' => 'admin',
            'password' => ''
        ];

        $resultado = $this->controller->autenticar($datos);

        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('errors', $resultado);
        $this->assertContains('La contraseña es requerida', $resultado['errors']);
    }

    /**
     * Test: Cerrar sesión exitoso
     */
    public function testCerrarSesionExitoso()
    {
        // Mock del servicio que retorna éxito
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('cerrarSesion')
            ->willReturn(true);

        $resultado = $this->controller->cerrarSesion();

        $this->assertTrue($resultado['success']);
        $this->assertEquals('Sesión cerrada exitosamente', $resultado['message']);
    }

    /**
     * Test: Cerrar sesión fallido
     */
    public function testCerrarSesionFallido()
    {
        // Mock del servicio que retorna error
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('cerrarSesion')
            ->willReturn(false);

        $resultado = $this->controller->cerrarSesion();

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Error al cerrar la sesión', $resultado['error']);
    }

    /**
     * Test: Verificar sesión activa
     */
    public function testVerificarSesionActiva()
    {
        $usuarioMock = [
            'id' => 1,
            'username' => 'admin',
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'email' => 'admin@eest2.edu.ar',
            'rol' => 'admin'
        ];

        // Mock del servicio que retorna usuario
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('verificarSesion')
            ->willReturn($usuarioMock);

        $resultado = $this->controller->verificarSesion();

        $this->assertTrue($resultado['success']);
        $this->assertEquals('Sesión activa', $resultado['message']);
        $this->assertEquals($usuarioMock, $resultado['data']);
    }

    /**
     * Test: Verificar sesión inactiva
     */
    public function testVerificarSesionInactiva()
    {
        // Mock del servicio que retorna null
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('verificarSesion')
            ->willReturn(null);

        $resultado = $this->controller->verificarSesion();

        $this->assertFalse($resultado['success']);
        $this->assertEquals('No hay sesión activa', $resultado['error']);
    }

    /**
     * Test: Cambiar contraseña exitoso
     */
    public function testCambiarPasswordExitoso()
    {
        $usuarioMock = [
            'id' => 1,
            'username' => 'admin',
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'email' => 'admin@eest2.edu.ar',
            'rol' => 'admin'
        ];

        $datos = [
            'password_actual' => 'password_actual',
            'password_nuevo' => 'password_nuevo',
            'password_confirmacion' => 'password_nuevo'
        ];

        // Mock de verificarSesion
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('verificarSesion')
            ->willReturn($usuarioMock);

        // Mock de cambiarPassword
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('cambiarPassword')
            ->with(1, 'password_actual', 'password_nuevo')
            ->willReturn([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ]);

        $resultado = $this->controller->cambiarPassword($datos);

        $this->assertTrue($resultado['success']);
        $this->assertEquals('Contraseña actualizada exitosamente', $resultado['message']);
    }

    /**
     * Test: Cambiar contraseña sin sesión activa
     */
    public function testCambiarPasswordSinSesion()
    {
        $datos = [
            'password_actual' => 'password_actual',
            'password_nuevo' => 'password_nuevo',
            'password_confirmacion' => 'password_nuevo'
        ];

        // Mock de verificarSesion que retorna null
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('verificarSesion')
            ->willReturn(null);

        $resultado = $this->controller->cambiarPassword($datos);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('No hay sesión activa', $resultado['error']);
    }

    /**
     * Test: Validación de cambio de contraseña
     */
    public function testValidacionCambioPassword()
    {
        $usuarioMock = [
            'id' => 1,
            'username' => 'admin',
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'email' => 'admin@eest2.edu.ar',
            'rol' => 'admin'
        ];

        $datos = [
            'password_actual' => '',
            'password_nuevo' => 'password_nuevo',
            'password_confirmacion' => 'password_nuevo'
        ];

        // Mock de verificarSesion
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('verificarSesion')
            ->willReturn($usuarioMock);

        $resultado = $this->controller->cambiarPassword($datos);

        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('errors', $resultado);
        $this->assertContains('La contraseña actual es requerida', $resultado['errors']);
    }

    /**
     * Test: Verificar permiso
     */
    public function testVerificarPermiso()
    {
        // Mock del servicio que retorna true
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('tienePermiso')
            ->with('ver_estudiantes')
            ->willReturn(true);

        $resultado = $this->controller->verificarPermiso('ver_estudiantes');

        $this->assertTrue($resultado['success']);
        $this->assertEquals('ver_estudiantes', $resultado['data']['permiso']);
        $this->assertTrue($resultado['data']['tiene_permiso']);
    }

    /**
     * Test: Manejo de excepciones
     */
    public function testManejoExcepciones()
    {
        $datos = [
            'username' => 'admin',
            'password' => 'password'
        ];

        // Mock del servicio que lanza excepción
        $this->servicioAutenticacionMock
            ->expects($this->once())
            ->method('autenticar')
            ->willThrowException(new \Exception('Error de conexión'));

        $resultado = $this->controller->autenticar($datos);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Error interno del servidor', $resultado['error']);
    }
}
