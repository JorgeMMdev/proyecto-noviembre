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
    public function insertar(Servicio $servicio): Servicio;

    public function actualizar(Servicio $servicio): void;

    public function borrar(int $id): void;

    public function contarOfertas(int $id): int;
}
