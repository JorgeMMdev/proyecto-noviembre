<?php

namespace App\DAO\Interfaces;

use App\Modelo\OfertaProfesional;

interface OfertaProfesionalDAOInterface
{
    /** @return OfertaProfesional[] */

    public function crearDesdeServicio(int $idUsuario, int $idServicio): bool;

    public function listarPorUsuario(int $idUsuario): array;

    public function buscarPorId(int $id): ?OfertaProfesional;

    public function actualizar(OfertaProfesional $oferta): void;

    public function listarPorServicio(int $idServicio): array;
}
