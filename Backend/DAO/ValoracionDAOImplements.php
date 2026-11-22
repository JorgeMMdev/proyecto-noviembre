<?php

namespace App\DAO;

use App\DAO\Interfaces\ValoracionDAOInterface;
use App\Modelo\Valoracion;

final class ValoracionDAOImplements implements ValoracionDAOInterface
{
    public function insertar(Valoracion $valoracion): Valoracion
    {
        $pdo = BD::conectar();

        $stmt = $pdo->prepare(
            'INSERT INTO valoraciones (id_solicitud, puntuacion, comentario)
             VALUES (:id_solicitud, :puntuacion, :comentario)'
        );
        $stmt->execute([
            'id_solicitud' => $valoracion->getIdSolicitud(),
            'puntuacion'   => $valoracion->getPuntuacion(),
            'comentario'   => $valoracion->getComentario(),
        ]);

        $valoracion->setIdValoracion((int) $pdo->lastInsertId());

        return $valoracion;
    }
}