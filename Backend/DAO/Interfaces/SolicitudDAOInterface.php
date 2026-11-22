<?php

namespace App\DAO\Interfaces;

use App\Modelo\EstadoSolicitud;
use App\Modelo\Solicitud;

interface SolicitudDAOInterface
{
    public function insertar(Solicitud $solicitud): Solicitud;

    public function listarRecibidas(int $idProfesional): array;

    public function listarEnviadas(int $idCliente): array;

    public function buscarPorId(int $id, bool $bloquear = false): ?array;

    public function cambiarEstado(int $id, EstadoSolicitud $actual, EstadoSolicitud $nuevo): bool;

    public function registrarConfirmacion(int $id, bool $comoCliente, EstadoSolicitud $nuevo): void;
}