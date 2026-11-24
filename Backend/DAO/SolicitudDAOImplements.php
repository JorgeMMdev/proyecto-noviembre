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
                u.nombre AS otro_nombre, u.apellidos AS otro_apellidos,
                v.puntuacion, v.comentario
         FROM solicitudes s
         JOIN ofertas_profesionales o ON o.id_oferta    = s.id_oferta
         JOIN servicios sv            ON sv.id_servicio = o.id_servicio
         JOIN usuarios u              ON u.id_usuario   = s.id_solicitante
         LEFT JOIN valoraciones v     ON v.id_solicitud = s.id_solicitud
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
                u.nombre AS otro_nombre, u.apellidos AS otro_apellidos,
                v.puntuacion, v.comentario
         FROM solicitudes s
         JOIN ofertas_profesionales o ON o.id_oferta    = s.id_oferta
         JOIN servicios sv            ON sv.id_servicio = o.id_servicio
         JOIN usuarios u              ON u.id_usuario   = o.id_usuario
         LEFT JOIN valoraciones v     ON v.id_solicitud = s.id_solicitud
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

        /**
     * Solicitudes sin terminar en las que participa un usuario, como cliente o como profesional.
     * FOR UPDATE: se llama dentro de una transacción y bloquea esas filas hasta el commit.
     */
    public function listarAbiertasDe(int $idUsuario): array
    {
        $stmt = BD::conectar()->prepare(
            "SELECT s.id_solicitud, s.id_solicitante, s.precio_final, s.estado
             FROM solicitudes s
             JOIN ofertas_profesionales o ON o.id_oferta = s.id_oferta
             WHERE (s.id_solicitante = :id1 OR o.id_usuario = :id2)
               AND s.estado IN ('SOLICITADO', 'ACEPTADO', 'EN_EJECUCION')
             FOR UPDATE"
        );
        $stmt->execute(['id1' => $idUsuario, 'id2' => $idUsuario]);

        return $stmt->fetchAll();
    }

        /**
     * Todas las solicitudes, con cliente, profesional, servicio y valoración.
     * Con $estado, solo las de ese estado.
     */
    public function listarTodas(?EstadoSolicitud $estado = null): array
    {
        $sql = 'SELECT s.id_solicitud, s.fecha_propuesta, s.fecha_creacion, s.tiempo_final,
                       s.precio_final, s.estado,
                       sv.nombre_servicio,
                       cli.nombre AS cliente_nombre, cli.apellidos AS cliente_apellidos,
                       pro.nombre AS profesional_nombre, pro.apellidos AS profesional_apellidos,
                       v.puntuacion, v.comentario
                FROM solicitudes s
                JOIN ofertas_profesionales o ON o.id_oferta    = s.id_oferta
                JOIN servicios sv            ON sv.id_servicio = o.id_servicio
                JOIN usuarios cli            ON cli.id_usuario = s.id_solicitante
                JOIN usuarios pro            ON pro.id_usuario = o.id_usuario
                LEFT JOIN valoraciones v     ON v.id_solicitud = s.id_solicitud';

        $parametros = [];
        if ($estado !== null) {
            $sql .= ' WHERE s.estado = :estado';
            $parametros['estado'] = $estado->value;
        }

        $sql .= ' ORDER BY s.fecha_creacion DESC, s.id_solicitud DESC';

        $stmt = BD::conectar()->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /** Cifras generales para la cabecera del historial */
    public function resumenGlobal(): array
    {
        return BD::conectar()->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(estado = 'COMPLETADO'), 0) AS completados,
                    COALESCE(SUM(CASE WHEN estado = 'COMPLETADO' THEN precio_final END), 0) AS transferidos,
                    COALESCE(SUM(CASE WHEN estado IN ('SOLICITADO', 'ACEPTADO', 'EN_EJECUCION')
                                      THEN precio_final END), 0) AS en_movimiento
             FROM solicitudes"
        )->fetch();
    }
}
