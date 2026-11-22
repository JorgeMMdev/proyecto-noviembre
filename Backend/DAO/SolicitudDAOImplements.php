<?php

namespace App\DAO;

use App\DAO\Interfaces\SolicitudDAOInterface;
use App\Modelo\Solicitud;
use App\Modelo\EstadoSolicitud;

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

    public function buscarPorId(int $id, bool $bloquear = false): ?array
    {
        $sql = 'SELECT s.*, o.id_usuario AS id_profesional
            FROM solicitudes s
            JOIN ofertas_profesionales o ON o.id_oferta = s.id_oferta
            WHERE s.id_solicitud = :id';

        // Dentro de una transacción, FOR UPDATE bloquea la fila hasta el commit:
        // si llega otra petición sobre la misma solicitud, espera su turno.
        if ($bloquear) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = BD::conectar()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    public function cambiarEstado(int $id, EstadoSolicitud $actual, EstadoSolicitud $nuevo): bool
    {
        $stmt = BD::conectar()->prepare(
            'UPDATE solicitudes
         SET estado = :nuevo
         WHERE id_solicitud = :id AND estado = :actual'
        );
        $stmt->execute([
            'nuevo'  => $nuevo->value,
            'id'     => $id,
            'actual' => $actual->value,
        ]);

        return $stmt->rowCount() === 1;
    }

    // Marca la confirmación del cliente o del profesional y pone el nuevo estado
    public function registrarConfirmacion(int $id, bool $comoCliente, EstadoSolicitud $nuevo): void
    {
        // El nombre de la columna no puede ir como parámetro de PDO,
        // así que se elige entre dos valores fijos (nunca algo que venga del usuario)
        $columna = $comoCliente ? 'confirmado_cliente' : 'confirmado_prestador';

        $stmt = BD::conectar()->prepare(
            "UPDATE solicitudes SET $columna = 1, estado = :estado WHERE id_solicitud = :id"
        );
        $stmt->execute([
            'estado' => $nuevo->value,
            'id'     => $id,
        ]);
    }

    
}
