<?php

namespace App\DAO;

use App\DAO\Interfaces\SolicitudDAOInterface;
use App\Modelo\Solicitud;

final class SolicitudDAOImplements implements SolicitudDAOInterface
{
    public function insertar(Solicitud $solicitud): Solicitud
    {
        $pdo = BD::conectar();

        $stmt = $pdo->prepare(
            'INSERT INTO solicitudes (id_oferta, id_solicitante, precio_final, tiempo_final, fecha_propuesta)
             VALUES (:id_oferta, :id_solicitante, :precio_final, :tiempo_final, :fecha_propuesta)'
        );
        $stmt->execute([
            'id_oferta'       => $solicitud->getIdOferta(),
            'id_solicitante'  => $solicitud->getIdSolicitante(),
            'precio_final'    => $solicitud->getPrecioFinal(),
            'tiempo_final'    => $solicitud->getTiempoFinal(),
            'fecha_propuesta' => $solicitud->getFechaPropuesta()->format('Y-m-d H:i:s'),
        ]);

        $solicitud->setIdSolicitud((int) $pdo->lastInsertId());

        return $solicitud;
    }

    public function listarRecibidas(int $idProfesional): array
{
    $stmt = BD::conectar()->prepare(
        'SELECT s.id_solicitud, s.fecha_propuesta, s.tiempo_final, s.precio_final, s.estado,
                s.confirmado_cliente, s.confirmado_prestador, s.fecha_creacion,
                sv.nombre_servicio,
                u.nombre AS otro_nombre, u.apellidos AS otro_apellidos
         FROM solicitudes s
         JOIN ofertas_profesionales o ON o.id_oferta    = s.id_oferta
         JOIN servicios sv            ON sv.id_servicio = o.id_servicio
         JOIN usuarios u              ON u.id_usuario   = s.id_solicitante
         WHERE o.id_usuario = :id
         ORDER BY s.fecha_creacion DESC, s.id_solicitud DESC'
    );
    $stmt->execute(['id' => $idProfesional]);

    return $stmt->fetchAll();
}

public function listarEnviadas(int $idCliente): array
{
    $stmt = BD::conectar()->prepare(
        'SELECT s.id_solicitud, s.fecha_propuesta, s.tiempo_final, s.precio_final, s.estado,
                s.confirmado_cliente, s.confirmado_prestador, s.fecha_creacion,
                sv.nombre_servicio,
                u.nombre AS otro_nombre, u.apellidos AS otro_apellidos
         FROM solicitudes s
         JOIN ofertas_profesionales o ON o.id_oferta    = s.id_oferta
         JOIN servicios sv            ON sv.id_servicio = o.id_servicio
         JOIN usuarios u              ON u.id_usuario   = o.id_usuario
         WHERE s.id_solicitante = :id
         ORDER BY s.fecha_creacion DESC, s.id_solicitud DESC'
    );
    $stmt->execute(['id' => $idCliente]);

    return $stmt->fetchAll();
}
}