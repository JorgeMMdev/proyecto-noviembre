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
            (int) $fila['saldo']
        );
    }
}