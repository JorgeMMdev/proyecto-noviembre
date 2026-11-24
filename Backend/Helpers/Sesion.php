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

    /** Guarda un mensaje para mostrarlo en la siguiente página (tras una redirección). */
    public static function flash(string $mensaje, string $tipo = 'exito'): void
    {
        $_SESSION['flash'] = ['mensaje' => $mensaje, 'tipo' => $tipo];
    }

    /** Devuelve el mensaje pendiente y lo borra, para que se vea una sola vez. */
    public static function tomarFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
