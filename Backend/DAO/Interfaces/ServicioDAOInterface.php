<?php

namespace App\DAO\Interfaces;

use App\Modelo\Servicio;

interface ServicioDAOInterface
{
    /** @return Servicio[] */
    public function listar(): array;

    /** @return Servicio[] */
    public function buscar(?string $texto, ?int $idCategoria): array;

    public function buscarPorId(int $id): ?Servicio;
}