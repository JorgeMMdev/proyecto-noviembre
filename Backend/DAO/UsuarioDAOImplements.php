<?php

namespace App\DAO;

use App\DAO\Interfaces\UsuarioDAOInterface;
use App\Modelo\Rol;
use App\Modelo\Usuario;
use DateTime;

final class UsuarioDAOImplements implements UsuarioDAOInterface
{
    public function buscarPorId(int $id): ?Usuario
    {
        $stmt = BD::conectar()->prepare('SELECT * FROM usuarios WHERE id_usuario = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila ? $this->mapear($fila) : null;
    }

    public function buscarPorCorreo(string $correo): ?Usuario
    {
        $stmt = BD::conectar()->prepare('SELECT * FROM usuarios WHERE correo = :correo');
        $stmt->execute(['correo' => $correo]);
        $fila = $stmt->fetch();

        return $fila ? $this->mapear($fila) : null;
    }

    public function insertar(Usuario $usuario): Usuario
    {
        $pdo = BD::conectar();

        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre, apellidos, correo, contrasena, fecha_nacimiento, rol, saldo)
             VALUES (:nombre, :apellidos, :correo, :contrasena, :fecha_nacimiento, :rol, :saldo)'
        );
        $stmt->execute([
            'nombre'           => $usuario->getNombre(),
            'apellidos'        => $usuario->getApellidos(),
            'correo'           => $usuario->getCorreo(),
            'contrasena'       => $usuario->getContrasena(),
            'fecha_nacimiento' => $usuario->getFechaNacimiento()->format('Y-m-d'),
            'rol'              => $usuario->getRol()->value,
            'saldo'            => $usuario->getSaldo(),
        ]);

        $usuario->setIdUsuario((int) $pdo->lastInsertId());

        return $usuario;
    }

    private function mapear(array $fila): Usuario
    {
        return new Usuario(
            (int) $fila['id_usuario'],
            $fila['nombre'],
            $fila['apellidos'],
            $fila['correo'],
            $fila['contrasena'],
            new DateTime($fila['fecha_nacimiento']),
            Rol::from($fila['rol']),
            $fila['valoracion_media'] !== null ? (float) $fila['valoracion_media'] : null,
            (int) $fila['saldo'],
            (bool) $fila['activo']
        );
    }

    public function estadisticas(int $idUsuario): array
    {
        $stmt = BD::conectar()->prepare(
            "SELECT
            (SELECT COUNT(*) FROM ofertas_profesionales
              WHERE id_usuario = :id1 AND activo = 1) AS ofertas,
            (SELECT COUNT(*) FROM solicitudes s
              JOIN ofertas_profesionales o ON o.id_oferta = s.id_oferta
              WHERE o.id_usuario = :id2 AND s.estado = 'COMPLETADO') AS completados,
            (SELECT COUNT(*) FROM valoraciones v
              JOIN solicitudes s ON s.id_solicitud = v.id_solicitud
              JOIN ofertas_profesionales o ON o.id_oferta = s.id_oferta
              WHERE o.id_usuario = :id3) AS valoraciones,
            (SELECT COALESCE(SUM(precio_final), 0) FROM solicitudes
              WHERE id_solicitante = :id4
                AND estado IN ('SOLICITADO', 'ACEPTADO', 'EN_EJECUCION')) AS en_movimiento"
        );
        $stmt->execute([
            'id1' => $idUsuario,
            'id2' => $idUsuario,
            'id3' => $idUsuario,
            'id4' => $idUsuario,
        ]);

        return $stmt->fetch();
    }

    public function restarSaldo(int $idUsuario, int $puntos): bool
    {
        $stmt = BD::conectar()->prepare(
            'UPDATE usuarios
         SET saldo = saldo - :puntos
         WHERE id_usuario = :id AND saldo >= :puntos2'
        );
        $stmt->execute([
            'puntos'  => $puntos,
            'puntos2' => $puntos,
            'id'      => $idUsuario,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function sumarSaldo(int $idUsuario, int $puntos): void
    {
        $stmt = BD::conectar()->prepare(
            'UPDATE usuarios SET saldo = saldo + :puntos WHERE id_usuario = :id'
        );
        $stmt->execute([
            'puntos' => $puntos,
            'id'     => $idUsuario,
        ]);
    }

    // Recalcula la media de todas las valoraciones que ha recibido un profesional
    public function actualizarValoracionMedia(int $idUsuario): void
    {
        $stmt = BD::conectar()->prepare(
            'UPDATE usuarios
         SET valoracion_media = (
             SELECT AVG(v.puntuacion)
             FROM valoraciones v
             JOIN solicitudes s           ON s.id_solicitud = v.id_solicitud
             JOIN ofertas_profesionales o ON o.id_oferta    = s.id_oferta
             WHERE o.id_usuario = :id1
         )
         WHERE id_usuario = :id2'
        );
        $stmt->execute(['id1' => $idUsuario, 'id2' => $idUsuario]);
    }

        /** Todos los usuarios con lo que necesita ver el admin. Devuelve filas. */
    public function listarParaAdmin(): array
    {
        return BD::conectar()->query(
            "SELECT u.id_usuario, u.nombre, u.apellidos, u.correo, u.rol, u.saldo,
                    u.valoracion_media, u.activo,
                    (SELECT COUNT(*) FROM ofertas_profesionales o
                      WHERE o.id_usuario = u.id_usuario AND o.activo = 1) AS ofertas,
                    (SELECT COUNT(*) FROM solicitudes s
                      JOIN ofertas_profesionales o ON o.id_oferta = s.id_oferta
                      WHERE (s.id_solicitante = u.id_usuario OR o.id_usuario = u.id_usuario)
                        AND s.estado IN ('SOLICITADO', 'ACEPTADO', 'EN_EJECUCION')) AS abiertas
             FROM usuarios u
             ORDER BY u.activo DESC, u.rol, u.nombre, u.apellidos"
        )->fetchAll();
    }

    public function cambiarActivo(int $idUsuario, bool $activo): void
    {
        $stmt = BD::conectar()->prepare('UPDATE usuarios SET activo = :activo WHERE id_usuario = :id');
        $stmt->execute(['activo' => $activo ? 1 : 0, 'id' => $idUsuario]);
    }
}
