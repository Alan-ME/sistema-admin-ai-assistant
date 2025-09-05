<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use SistemaAdmin\Models\Estudiante;
use SistemaAdmin\Exceptions\EstudianteNoEncontradoException;

/**
 * Tests unitarios para el modelo Estudiante
 */
class EstudianteTest extends TestCase
{
    /**
     * Test: Crear estudiante con datos válidos
     */
    public function testCrearEstudianteConDatosValidos()
    {
        $datos = [
            'dni' => '12345678',
            'apellido' => 'García',
            'nombre' => 'Juan',
            'fecha_nacimiento' => '2005-03-15',
            'domicilio' => 'Calle 123, Ciudad',
            'telefono' => '223-1234567',
            'email' => 'juan.garcia@email.com',
            'curso_id' => 1
        ];

        $estudiante = new Estudiante($datos);

        $this->assertEquals('12345678', $estudiante->getDni());
        $this->assertEquals('García', $estudiante->getApellido());
        $this->assertEquals('Juan', $estudiante->getNombre());
        $this->assertEquals('2005-03-15', $estudiante->getFechaNacimiento()->format('Y-m-d'));
        $this->assertEquals('Calle 123, Ciudad', $estudiante->getDomicilio());
        $this->assertEquals('223-1234567', $estudiante->getTelefono());
        $this->assertEquals('juan.garcia@email.com', $estudiante->getEmail());
        $this->assertEquals(1, $estudiante->getCursoId());
    }

    /**
     * Test: Validar DNI requerido
     */
    public function testValidarDniRequerido()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El DNI es requerido');

        $datos = [
            'apellido' => 'García',
            'nombre' => 'Juan',
            'curso_id' => 1
        ];

        new Estudiante($datos);
    }

    /**
     * Test: Validar apellido requerido
     */
    public function testValidarApellidoRequerido()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El apellido es requerido');

        $datos = [
            'dni' => '12345678',
            'nombre' => 'Juan',
            'curso_id' => 1
        ];

        new Estudiante($datos);
    }

    /**
     * Test: Validar nombre requerido
     */
    public function testValidarNombreRequerido()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre es requerido');

        $datos = [
            'dni' => '12345678',
            'apellido' => 'García',
            'curso_id' => 1
        ];

        new Estudiante($datos);
    }

    /**
     * Test: Validar formato de email
     */
    public function testValidarFormatoEmail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El formato del email no es válido');

        $datos = [
            'dni' => '12345678',
            'apellido' => 'García',
            'nombre' => 'Juan',
            'email' => 'email-invalido',
            'curso_id' => 1
        ];

        new Estudiante($datos);
    }

    /**
     * Test: Validar formato de fecha de nacimiento
     */
    public function testValidarFormatoFechaNacimiento()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El formato de la fecha de nacimiento no es válido');

        $datos = [
            'dni' => '12345678',
            'apellido' => 'García',
            'nombre' => 'Juan',
            'fecha_nacimiento' => 'fecha-invalida',
            'curso_id' => 1
        ];

        new Estudiante($datos);
    }

    /**
     * Test: Calcular edad correctamente
     */
    public function testCalcularEdad()
    {
        $fechaNacimiento = (new \DateTime())->modify('-18 years')->format('Y-m-d');
        
        $datos = [
            'dni' => '12345678',
            'apellido' => 'García',
            'nombre' => 'Juan',
            'fecha_nacimiento' => $fechaNacimiento,
            'curso_id' => 1
        ];

        $estudiante = new Estudiante($datos);
        $this->assertEquals(18, $estudiante->getEdad());
    }

    /**
     * Test: Obtener nombre completo
     */
    public function testObtenerNombreCompleto()
    {
        $datos = [
            'dni' => '12345678',
            'apellido' => 'García',
            'nombre' => 'Juan',
            'curso_id' => 1
        ];

        $estudiante = new Estudiante($datos);
        $this->assertEquals('García, Juan', $estudiante->getNombreCompleto());
    }

    /**
     * Test: Convertir a array
     */
    public function testToArray()
    {
        $datos = [
            'dni' => '12345678',
            'apellido' => 'García',
            'nombre' => 'Juan',
            'fecha_nacimiento' => '2005-03-15',
            'curso_id' => 1
        ];

        $estudiante = new Estudiante($datos);
        $array = $estudiante->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('12345678', $array['dni']);
        $this->assertEquals('García', $array['apellido']);
        $this->assertEquals('Juan', $array['nombre']);
        $this->assertEquals('2005-03-15', $array['fecha_nacimiento']);
        $this->assertEquals(1, $array['curso_id']);
    }

    /**
     * Test: Validar DNI con formato incorrecto
     */
    public function testValidarDniFormatoIncorrecto()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El DNI debe contener solo números');

        $datos = [
            'dni' => '1234567a',
            'apellido' => 'García',
            'nombre' => 'Juan',
            'curso_id' => 1
        ];

        new Estudiante($datos);
    }

    /**
     * Test: Validar DNI muy corto
     */
    public function testValidarDniMuyCorto()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El DNI debe tener entre 7 y 8 dígitos');

        $datos = [
            'dni' => '123456',
            'apellido' => 'García',
            'nombre' => 'Juan',
            'curso_id' => 1
        ];

        new Estudiante($datos);
    }

    /**
     * Test: Validar DNI muy largo
     */
    public function testValidarDniMuyLargo()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El DNI debe tener entre 7 y 8 dígitos');

        $datos = [
            'dni' => '123456789',
            'apellido' => 'García',
            'nombre' => 'Juan',
            'curso_id' => 1
        ];

        new Estudiante($datos);
    }
}
