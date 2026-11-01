<?php
namespace App\Modelo;

use DateTime;

class Usuario
{

    public function __construct(
        private int $idUsuario,
        private string $nombre,
        private string $apellidos,
        private string $correo,
        private string $contrasena,
        private DateTime $fechaNacimiento,
        private Rol $rol = Rol::PROFESIONAL,
        private ?float $valoracionMedia = null,
        private int $saldo = 0
    ) {}

    //getters
    public function getIdUsuario(): ?int
    {
        return $this->idUsuario;
    }
 
    public function getNombre(): string
    {
        return $this->nombre;
    }
 
    public function getApellidos(): string
    {
        return $this->apellidos;
    }
 
    public function getCorreo(): string
    {
        return $this->correo;
    }
 
    public function getContrasena(): string
    {
        return $this->contrasena;
    }
 
    public function getFechaNacimiento(): DateTime
    {
        return $this->fechaNacimiento;
    }
 
    public function getRol(): Rol
    {
        return $this->rol;
    }
 
    public function getValoracionMedia(): ?float
    {
        return $this->valoracionMedia;
    }
 
    public function getSaldo(): int
    {
        return $this->saldo;
    }

    //setters
 
    public function setIdUsuario(?int $idUsuario): void
    {
        $this->idUsuario = $idUsuario;
    }
 
    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }
 
    public function setApellidos(string $apellidos): void
    {
        $this->apellidos = $apellidos;
    }
 
    public function setCorreo(string $correo): void
    {
        $this->correo = $correo;
    }
 
    public function setContrasena(string $contrasena): void
    {
        $this->contrasena = $contrasena;
    }
 
    public function setFechaNacimiento(DateTime $fechaNacimiento): void
    {
        $this->fechaNacimiento = $fechaNacimiento;
    }
 
    public function setRol(Rol $rol): void
    {
        $this->rol = $rol;
    }
 
    public function setValoracionMedia(?float $valoracionMedia): void
    {
        $this->valoracionMedia = $valoracionMedia;
    }
 
    public function setSaldo(int $saldo): void
    {
        $this->saldo = $saldo;
    }
}
