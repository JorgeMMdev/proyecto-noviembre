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

    public function insertar(Categoria $categoria): Categoria
    {
        $pdo = BD::conectar();

        $stmt = $pdo->prepare(
            'INSERT INTO categorias (nombre_categoria, descripcion) VALUES (:nombre, :descripcion)'
        );
        $stmt->execute([
            'nombre'      => $categoria->getNombreCategoria(),
            'descripcion' => $categoria->getDescripcion(),
        ]);

        $categoria->setIdCategoria((int) $pdo->lastInsertId());

        return $categoria;
    }

    public function actualizar(Categoria $categoria): void
    {
        $stmt = BD::conectar()->prepare(
            'UPDATE categorias SET nombre_categoria = :nombre, descripcion = :descripcion
             WHERE id_categoria = :id'
        );
        $stmt->execute([
            'nombre'      => $categoria->getNombreCategoria(),
            'descripcion' => $categoria->getDescripcion(),
            'id'          => $categoria->getIdCategoria(),
        ]);
    }

    public function borrar(int $id): void
    {
        $stmt = BD::conectar()->prepare('DELETE FROM categorias WHERE id_categoria = :id');
        $stmt->execute(['id' => $id]);
    }

    // Cuántos servicios del catálogo pertenecen a esta categoría
    public function contarServicios(int $id): int
    {
        $stmt = BD::conectar()->prepare('SELECT COUNT(*) FROM servicios WHERE id_categoria = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
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