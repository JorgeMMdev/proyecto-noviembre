<?php

namespace App\DAO\Interfaces;

use App\Modelo\Valoracion;

interface  ValoracionDAOInterface{
    public function insertar(Valoracion $valoracion): Valoracion;
}
