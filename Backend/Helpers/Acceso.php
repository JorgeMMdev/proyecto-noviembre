<?php

namespace App\Helpers;

/**
 * Comprobaciones de permisos.
 * Si el acceso no está permitido, ya responden (con JSON o con una redirección).
 */
class Acceso
{
    /** Para la API: true si es el administrador; si no, responde 401/403 en JSON y devuelve false. */
    public static function soloAdmin(): bool
    {
        if (Sesion::leer('usuario') === null) {
            Respuesta::json(["error" => "Debes iniciar sesión."], 401);
            return false;
        }
        if (!Sesion::esAdmin()) {
            Respuesta::json(["error" => "Solo el administrador puede hacer esto."], 403);
            return false;
        }
        return true;
    }

    /** Para las páginas web: si no es el administrador, redirige y corta la ejecución. */
    public static function exigirAdmin(): void
    {
        if (Sesion::leer('usuario') === null) {
            header('Location: /auth/login');
            exit;
        }
        if (!Sesion::esAdmin()) {
            header('Location: /');
            exit;
        }
    }

        /** Para las páginas web: si no hay sesión, redirige al login. Devuelve el usuario de la sesión. */
    public static function exigirLogin(): array
    {
        $usuario = Sesion::leer('usuario');
        if ($usuario === null) {
            header('Location: /auth/login');
            exit;
        }
        return $usuario;
    }
}