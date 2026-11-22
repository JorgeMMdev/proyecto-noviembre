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

    public function insertar(Servicio $servicio): Servicio
    {
        $pdo = BD::conectar();

        $stmt = $pdo->prepare(
            'INSERT INTO servicios (id_categoria, nombre_servicio, precio_medio_estimado, tiempo_medio_estimado, descripcion)
         VALUES (:id_categoria, :nombre, :precio, :tiempo, :descripcion)'
        );
        $stmt->execute($this->parametros($servicio));

        $servicio->setIdServicio((int) $pdo->lastInsertId());

        return $servicio;
    }

    public function actualizar(Servicio $servicio): void
    {
        $stmt = BD::conectar()->prepare(
            'UPDATE servicios
         SET id_categoria = :id_categoria, nombre_servicio = :nombre,
             precio_medio_estimado = :precio, tiempo_medio_estimado = :tiempo, descripcion = :descripcion
         WHERE id_servicio = :id'
        );
        $stmt->execute($this->parametros($servicio) + ['id' => $servicio->getIdServicio()]);
    }

    public function borrar(int $id): void
    {
        $stmt = BD::conectar()->prepare('DELETE FROM servicios WHERE id_servicio = :id');
        $stmt->execute(['id' => $id]);
    }

    // Cuántos profesionales ofrecen este servicio (activos o no)
    public function contarOfertas(int $id): int
    {
        $stmt = BD::conectar()->prepare('SELECT COUNT(*) FROM ofertas_profesionales WHERE id_servicio = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    // Los valores comunes a insertar y actualizar
    private function parametros(Servicio $servicio): array
    {
        return [
            'id_categoria' => $servicio->getIdCategoria(),
            'nombre'       => $servicio->getNombreServicio(),
            'precio'       => $servicio->getPrecioMedioEstimado(),
            'tiempo'       => $servicio->getTiempoMedioEstimado(),
            'descripcion'  => $servicio->getDescripcion(),
        ];
    }
}
