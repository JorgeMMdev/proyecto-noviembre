<?php

namespace App\DAO;

use App\DAO\Interfaces\OfertaProfesionalDAOInterface;

final class OfertaProfesionalDAOImplements implements OfertaProfesionalDAOInterface
{
    public function crearDesdeServicio(int $idUsuario, int $idServicio): bool
{
    $pdo = BD::conectar();

    $stmt = $pdo->prepare(
        'INSERT INTO ofertas_profesionales (id_usuario, id_servicio, puntos_hora)
        SELECT :id_usuario, id_servicio, precio_medio_estimado
        FROM servicios
        WHERE id_servicio = :id_servicio'
    );
    $stmt->execute([
        'id_usuario'  => $idUsuario,
        'id_servicio' => $idServicio,
    ]);

    return $stmt->rowCount() === 1;
}
}
