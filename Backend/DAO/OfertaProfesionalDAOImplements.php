<?php

namespace App\DAO;

use App\DAO\Interfaces\OfertaProfesionalDAOInterface;
use App\Modelo\OfertaProfesional;
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

    public function listarPorUsuario(int $idUsuario): array
    {
        $stmt = BD::conectar()->prepare(
            'SELECT o.id_oferta, o.puntos_hora, o.descripcion_personal, o.activo,
                s.nombre_servicio, c.nombre_categoria
         FROM ofertas_profesionales o
         JOIN servicios s  ON s.id_servicio  = o.id_servicio
         JOIN categorias c ON c.id_categoria = s.id_categoria
         WHERE o.id_usuario = :id_usuario
         ORDER BY o.activo DESC, s.nombre_servicio'
        );
        $stmt->execute(['id_usuario' => $idUsuario]);

        return $stmt->fetchAll();
    }

     public function listarPorServicio(int $idServicio): array
    {
        $stmt = BD::conectar()->prepare(
            'SELECT o.id_oferta, o.puntos_hora, o.descripcion_personal, u.nombre, u.apellidos, u.valoracion_media
            FROM ofertas_profesionales o
            JOIN usuarios u ON u.id_usuario = o.id_usuario  
            WHERE o.id_servicio = :id_servicio AND o.activo = TRUE
            ORDER BY u.valoracion_media DESC'
        );
        $stmt->execute(['id_servicio' => $idServicio]);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?OfertaProfesional
    {
        $stmt = BD::conectar()->prepare('SELECT * FROM ofertas_profesionales WHERE id_oferta = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        if (!$fila) {
            return null;
        }

        return new OfertaProfesional(
            (int) $fila['id_oferta'],
            (int) $fila['id_usuario'],
            (int) $fila['id_servicio'],
            (int) $fila['puntos_hora'],
            $fila['descripcion_personal'],
            (bool) $fila['activo']
        );
    }

    public function actualizar(OfertaProfesional $oferta): void
    {
        $stmt = BD::conectar()->prepare(
            'UPDATE ofertas_profesionales
         SET puntos_hora = :puntos_hora,
             descripcion_personal = :descripcion_personal,
             activo = :activo
         WHERE id_oferta = :id_oferta'
        );
        $stmt->execute([
            'puntos_hora'          => $oferta->getPuntosHora(),
            'descripcion_personal' => $oferta->getDescripcionPersonal(),
            'activo'               => $oferta->isActivo() ? 1 : 0,
            'id_oferta'            => $oferta->getIdOferta(),
        ]);
    }

    
}
