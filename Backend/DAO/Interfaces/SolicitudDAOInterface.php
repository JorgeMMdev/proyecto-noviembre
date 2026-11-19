<?php

namespace App\DAO\Interfaces;

use App\Modelo\Solicitud;
use App\Modelo\EstadoSolicitud;


interface SolicitudDAOInterface
{
    public function insertar(Solicitud $solicitud): Solicitud;

    public function listarRecibidas(int $idProfesional): array;

    public function listarEnviadas(int $idCliente): array;

    public function buscarPorId(int $id): ?array;

    public function cambiarEstado(int $id, EstadoSolicitud $estado): void;
}
