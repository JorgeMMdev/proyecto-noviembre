<?php

namespace App\DAO;

use App\DAO\Interfaces\CategoriaDAOInterface;
use App\Modelo\Categoria;

class CategoriaDAOImplements implements CategoriaDAOInterface
{
    private const COLUMNAS = 'id_categoria, nombre_categoria, descripcion';

    public function listar(): array
    {
        $filas = BD::conectar()
            ->query('SELECT ' . self::COLUMNAS . ' FROM categorias ORDER BY nombre_categoria')
            ->fetchAll();

        return array_map(fn(array $fila) => $this->mapear($fila), $filas);
    }

    public function buscarPorId(int $id): ?Categoria
    {
        $stmt = BD::conectar()->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM categorias WHERE id_categoria = :id'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila ? $this->mapear($fila) : null;
    }

    private function mapear(array $fila): Categoria
    {
        return new Categoria(
            (int) $fila['id_categoria'],
            $fila['nombre_categoria'],
            $fila['descripcion']
        );
    }
}