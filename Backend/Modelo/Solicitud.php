<?php

namespace App\Modelo;

use DateTimeImmutable;

class Solicitud
{
    public function __construct(
        private ?int $idSolicitud,
        private int $idOferta,
        private int $idSolicitante,
        private int $precioFinal,
        private float $tiempoFinal,
        private DateTimeImmutable $fechaPropuesta,
        private EstadoSolicitud $estado = EstadoSolicitud::SOLICITADO,
        private bool $confirmadoCliente = false,
        private bool $confirmadoPrestador = false,
        private ?DateTimeImmutable $fechaCreacion = null
    ) {
    }

    public function getIdSolicitud(): ?int
    {
        return $this->idSolicitud;
    }

    public function getIdOferta(): int
    {
        return $this->idOferta;
    }

    public function getIdSolicitante(): int
    {
        return $this->idSolicitante;
    }

    public function getPrecioFinal(): int
    {
        return $this->precioFinal;
    }

    public function getTiempoFinal(): float
    {
        return $this->tiempoFinal;
    }

    public function getFechaPropuesta(): DateTimeImmutable
    {
        return $this->fechaPropuesta;
    }

    public function getEstado(): EstadoSolicitud
    {
        return $this->estado;
    }

    public function isConfirmadoCliente(): bool
    {
        return $this->confirmadoCliente;
    }

    public function isConfirmadoPrestador(): bool
    {
        return $this->confirmadoPrestador;
    }

    public function getFechaCreacion(): ?DateTimeImmutable
    {
        return $this->fechaCreacion;
    }

    public function setIdSolicitud(?int $idSolicitud): void
    {
        $this->idSolicitud = $idSolicitud;
    }

    public function setIdOferta(int $idOferta): void
    {
        $this->idOferta = $idOferta;
    }

    public function setIdSolicitante(int $idSolicitante): void
    {
        $this->idSolicitante = $idSolicitante;
    }

    public function setPrecioFinal(int $precioFinal): void
    {
        $this->precioFinal = $precioFinal;
    }

    public function setTiempoFinal(float $tiempoFinal): void
    {
        $this->tiempoFinal = $tiempoFinal;
    }

    public function setFechaPropuesta(DateTimeImmutable $fechaPropuesta): void
    {
        $this->fechaPropuesta = $fechaPropuesta;
    }

    public function setEstado(EstadoSolicitud $estado): void
    {
        $this->estado = $estado;
    }

    public function setConfirmadoCliente(bool $confirmadoCliente): void
    {
        $this->confirmadoCliente = $confirmadoCliente;
    }

    public function setConfirmadoPrestador(bool $confirmadoPrestador): void
    {
        $this->confirmadoPrestador = $confirmadoPrestador;
    }

    public function setFechaCreacion(?DateTimeImmutable $fechaCreacion): void
    {
        $this->fechaCreacion = $fechaCreacion;
    }
}