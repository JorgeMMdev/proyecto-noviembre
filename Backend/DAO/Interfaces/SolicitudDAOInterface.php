<?php

namespace App\DAO\Interfaces;

use App\Modelo\Solicitud;

interface SolicitudDAOInterface
{
    public function insertar(Solicitud $solicitud): Solicitud;

    public function listarRecibidas(int $idProfesional): array;

    public function listarEnviadas(int $idCliente): array;
}
