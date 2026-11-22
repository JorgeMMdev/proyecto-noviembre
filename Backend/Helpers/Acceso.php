<?php

namespace App\Helpers;

/**
 * Comprobaciones de permisos para los controladores de la API.
 * Si el acceso no está permitido, ya responden con el error.
 */
class Acceso
{
    /** Devuelve true si quien llama es el administrador; si no, responde 401/403 y devuelve false. */
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
}