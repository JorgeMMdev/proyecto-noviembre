<?php

namespace App\Modelo;

class OfertaProfesional
{
    public function __construct(
        private ?int $idOferta,
        private int $idUsuario,
        private int $idServicio,
        private int $puntosHora,
        private ?string $descripcionPersonal = null,
        private bool $activo = true
    ) {}

    //getters y setters
    public function getIdOferta(): ?int
    {
        return $this->idOferta;
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function getIdServicio(): int
    {
        return $this->idServicio;
    }

    public function getPuntosHora(): int
    {
        return $this->puntosHora;
    }

    public function getDescripcionPersonal(): ?string
    {
        return $this->descripcionPersonal;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setIdOferta(?int $idOferta): void
    {
        $this->idOferta = $idOferta;
    }

    public function setIdUsuario(int $idUsuario): void
    {
        $this->idUsuario = $idUsuario;
    }

    public function setIdServicio(int $idServicio): void
    {
        $this->idServicio = $idServicio;
    }

    public function setPuntosHora(int $puntosHora): void
    {
        $this->puntosHora = $puntosHora;
    }

    public function setDescripcionPersonal(?string $descripcionPersonal): void
    {
        $this->descripcionPersonal = $descripcionPersonal;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }
}