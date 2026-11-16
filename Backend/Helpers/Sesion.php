<?php

namespace App\Helpers;

class Sesion
{

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function leer(string $clave)
    {

        return $_SESSION[$clave] ?? null;
    }

    public static function existe(String $clave)
    {

        return isset($_SESSION[$clave]);
    }

    public static function escribir($clave, $valor): void
    {

        $_SESSION[$clave] = $valor;
    }

    public static function eliminar($clave)
    {

        unset($_SESSION[$clave]);
    }

    public static function destroy()
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function regenerar()
    {

        session_regenerate_id(true);
    }

    public static function esAdmin(): bool
    {
        return (self::leer('usuario')['rol'] ?? '') === 'ADMIN';
    }
}
