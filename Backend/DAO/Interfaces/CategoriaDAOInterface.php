<?php

namespace App\DAO\Interfaces;

use App\Modelo\Categoria;

interface CategoriaDAOInterface
{
    /** @return Categoria[] */
    public function listar(): array;

    public function buscarPorId(int $id): ?Categoria;
}