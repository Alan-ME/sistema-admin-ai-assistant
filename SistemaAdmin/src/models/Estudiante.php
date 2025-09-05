<?php

namespace SistemaAdmin\Models;

use DateTime;

/**
 * TDC (Tipo de Dato Concreto) - Modelo Estudiante
 * 
 * Representa un estudiante del sistema con validaciones internas
 * y métodos de negocio encapsulados.
 */
class Estudiante
{
    private ?int $id;
    private string $dni;
    private string $nombre;
    private string $apellido;
    private ?DateTime $fechaNacimiento;
    private ?string $grupoSanguineo;
    private ?string $obraSocial;
    private ?string $domicilio;
    private ?string $telefonoFijo;
    private ?string $telefonoCelular;
    private ?string $email;
    private ?int $cursoId;
    private bool $activo;

    public function __construct(
        string $dni,
        string $nombre,
        string $apellido,
        ?DateTime $fechaNacimiento = null,
        ?string $grupoSanguineo = null,
        ?string $obraSocial = null,
        ?string $domicilio = null,
        ?string $telefonoFijo = null,
        ?string $telefonoCelular = null,
        ?string $email = null,
        ?int $cursoId = null,
        bool $activo = true
    ) {
        $this->id = null;
        $this->setDni($dni);
        $this->setNombre($nombre);
        $this->setApellido($apellido);
        $this->fechaNacimiento = $fechaNacimiento;
        $this->grupoSanguineo = $grupoSanguineo;
        $this->obraSocial = $obraSocial;
        $this->domicilio = $domicilio;
        $this->telefonoFijo = $telefonoFijo;
        $this->telefonoCelular = $telefonoCelular;
        $this->email = $email;
        $this->cursoId = $cursoId;
        $this->activo = $activo;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDni(): string
    {
        return $this->dni;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function getNombreCompleto(): string
    {
        return $this->apellido . ', ' . $this->nombre;
    }

    public function getFechaNacimiento(): ?DateTime
    {
        return $this->fechaNacimiento;
    }

    public function getEdad(): int
    {
        if ($this->fechaNacimiento === null) {
            return 0;
        }

        $hoy = new DateTime();
        return $hoy->diff($this->fechaNacimiento)->y;
    }

    public function getGrupoSanguineo(): ?string
    {
        return $this->grupoSanguineo;
    }

    public function getObraSocial(): ?string
    {
        return $this->obraSocial;
    }

    public function getDomicilio(): ?string
    {
        return $this->domicilio;
    }

    public function getTelefonoFijo(): ?string
    {
        return $this->telefonoFijo;
    }

    public function getTelefonoCelular(): ?string
    {
        return $this->telefonoCelular;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getCursoId(): ?int
    {
        return $this->cursoId;
    }

    public function esActivo(): bool
    {
        return $this->activo;
    }

    // Setters con validaciones
    public function setDni(string $dni): void
    {
        if (!$this->validarDni($dni)) {
            throw new \InvalidArgumentException("DNI inválido: $dni");
        }
        $this->dni = $dni;
    }

    public function setNombre(string $nombre): void
    {
        $nombre = trim($nombre);
        if (empty($nombre)) {
            throw new \InvalidArgumentException("El nombre no puede estar vacío");
        }
        if (strlen($nombre) > 100) {
            throw new \InvalidArgumentException("El nombre no puede exceder 100 caracteres");
        }
        $this->nombre = $nombre;
    }

    public function setApellido(string $apellido): void
    {
        $apellido = trim($apellido);
        if (empty($apellido)) {
            throw new \InvalidArgumentException("El apellido no puede estar vacío");
        }
        if (strlen($apellido) > 100) {
            throw new \InvalidArgumentException("El apellido no puede exceder 100 caracteres");
        }
        $this->apellido = $apellido;
    }

    public function setCurso(int $cursoId): void
    {
        if ($cursoId <= 0) {
            throw new \InvalidArgumentException("ID de curso inválido");
        }
        $this->cursoId = $cursoId;
    }

    public function setEmail(?string $email): void
    {
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Email inválido: $email");
        }
        $this->email = $email;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }

    // Métodos de negocio
    public function validarDni(string $dni): bool
    {
        // Validación básica de DNI argentino (7-8 dígitos)
        $dni = preg_replace('/[^0-9]/', '', $dni);
        return strlen($dni) >= 7 && strlen($dni) <= 8;
    }

    public function esMayorDeEdad(): bool
    {
        return $this->getEdad() >= 18;
    }

    public function tieneContacto(): bool
    {
        return !empty($this->telefonoFijo) || !empty($this->telefonoCelular) || !empty($this->email);
    }

    // Método para establecer ID (solo para uso interno de mappers)
    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new \RuntimeException("No se puede modificar el ID de un estudiante existente");
        }
        $this->id = $id;
    }

    // Método para convertir a array (útil para serialización)
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'dni' => $this->dni,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->getNombreCompleto(),
            'fecha_nacimiento' => $this->fechaNacimiento?->format('Y-m-d'),
            'edad' => $this->getEdad(),
            'grupo_sanguineo' => $this->grupoSanguineo,
            'obra_social' => $this->obraSocial,
            'domicilio' => $this->domicilio,
            'telefono_fijo' => $this->telefonoFijo,
            'telefono_celular' => $this->telefonoCelular,
            'email' => $this->email,
            'curso_id' => $this->cursoId,
            'activo' => $this->activo,
            'es_mayor_edad' => $this->esMayorDeEdad(),
            'tiene_contacto' => $this->tieneContacto()
        ];
    }
}
