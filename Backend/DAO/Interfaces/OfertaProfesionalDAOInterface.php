<?php

namespace App\DAO\Interfaces;

use App\Modelo\OfertaProfesional;

interface OfertaProfesionalDAOInterface
{
    /** @return OfertaProfesional[] */

    public function crearDesdeServicio(int $idUsuario, int $idServicio): bool;
}