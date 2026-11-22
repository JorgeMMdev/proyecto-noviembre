<?php

namespace App\DAO\Interfaces;

use App\Modelo\Categoria;

interface CategoriaDAOInterface
{
    /** @return Categoria[] */
    public function listar(): array;

    public function buscarPorId(int $id): ?Categoria;

    public function insertar(Categoria $categoria): Categoria;

    public function actualizar(Categoria $categoria): void;

    public function borrar(int $id): void;

    public function contarServicios(int $id): int;
}