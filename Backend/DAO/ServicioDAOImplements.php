<?php

namespace App\DAO;

use App\DAO\Interfaces\ServicioDAOInterface;
use App\Modelo\Servicio;

class ServicioDAOImplements implements ServicioDAOInterface
{
    private const COLUMNAS =
        'id_servicio, id_categoria, nombre_servicio, precio_medio_estimado, tiempo_medio_estimado, descripcion';

    public function listar(): array
    {
        return $this->buscar(null, null);
    }

    public function buscar(?string $texto, ?int $idCategoria): array
    {
        $sql = 'SELECT ' . self::COLUMNAS . ' FROM servicios WHERE 1 = 1';
        $params = [];

        if ($texto !== null && $texto !== '') {
            // Un marcador distinto por cada uso: con prepares nativos no se puede repetir :texto
            $sql .= ' AND (nombre_servicio LIKE :texto1 OR descripcion LIKE :texto2)';
            $params['texto1'] = '%' . $texto . '%';
            $params['texto2'] = '%' . $texto . '%';
        }

        if ($idCategoria !== null) {
            $sql .= ' AND id_categoria = :categoria';
            $params['categoria'] = $idCategoria;
        }

        $sql .= ' ORDER BY nombre_servicio';

        $stmt = BD::conectar()->prepare($sql);
        $stmt->execute($params);

        return array_map(fn(array $fila) => $this->mapear($fila), $stmt->fetchAll());
    }

    public function buscarPorId(int $id): ?Servicio
    {
        $stmt = BD::conectar()->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM servicios WHERE id_servicio = :id'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila ? $this->mapear($fila) : null;
    }

    private function mapear(array $fila): Servicio
    {
        return new Servicio(
            (int) $fila['id_servicio'],
            (int) $fila['id_categoria'],
            $fila['nombre_servicio'],
            (int) $fila['precio_medio_estimado'],
            (float) $fila['tiempo_medio_estimado'],
            $fila['descripcion']
        );
    }
}