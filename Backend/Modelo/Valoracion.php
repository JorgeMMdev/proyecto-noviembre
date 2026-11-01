<?php

namespace App\Modelo;

use DateTime;

class Valoracion
{
    public function __construct(
        private ?int $idValoracion,
        private int $idSolicitud,
        private int $puntuacion,
        private ?string $comentario = null,
        private ?DateTime $fechaValoracion = null
    ) {
    }

    public function getIdValoracion(): ?int
    {
        return $this->idValoracion;
    }

    public function getIdSolicitud(): int
    {
        return $this->idSolicitud;
    }

    public function getPuntuacion(): int
    {
        return $this->puntuacion;
    }

    public function getComentario(): ?string
    {
        return $this->comentario;
    }

    public function getFechaValoracion(): ?DateTime
    {
        return $this->fechaValoracion;
    }

    public function setIdValoracion(?int $idValoracion): void
    {
        $this->idValoracion = $idValoracion;
    }

    public function setIdSolicitud(int $idSolicitud): void
    {
        $this->idSolicitud = $idSolicitud;
    }

    public function setPuntuacion(int $puntuacion): void
    {
        $this->puntuacion = $puntuacion;
    }

    public function setComentario(?string $comentario): void
    {
        $this->comentario = $comentario;
    }

    public function setFechaValoracion(?DateTime $fechaValoracion): void
    {
        $this->fechaValoracion = $fechaValoracion;
    }
}