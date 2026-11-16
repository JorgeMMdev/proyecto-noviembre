<?php

namespace App\Helpers;

use App\DAO\Interfaces\UsuarioDAOInterface;
use App\Modelo\Usuario;

class Autenticacion
{
    public function __construct(private UsuarioDAOInterface $usuarioDAO) {}

    public function verificarCredenciales(string $correo, string $contrasena): ?Usuario
    {
        $correo = trim(strtolower($correo));

        $usuario = $this->usuarioDAO->buscarPorCorreo($correo);

        if ($usuario === null) {
            return null;
        }

        if (!password_verify($contrasena, $usuario->getContrasena())) {
            return null;
        }

        return $usuario;
    }

    // Login completo en un paso: credenciales + sesión
    public function login(string $correo, string $contrasena): ?Usuario
    {
        $usuario = $this->verificarCredenciales($correo, $contrasena);

        if ($usuario === null) {
            return null;
        }

        $this->iniciarSesion($usuario);

        return $usuario;
    }

    public function iniciarSesion(Usuario $usuario): void
    {
        //Seguridad contra ataque de fijacion de sesion.
        Sesion::regenerar();

        Sesion::escribir('usuario', [
            'id' => $usuario->getIdUsuario(),
            'nombre' => $usuario->getNombre(),
            'rol' => $usuario->getRol()->value
        ]);
    }

    public function logout(): void
    {
        Sesion::destroy();
    }
}