<?php

namespace App\Helpers;

class Respuesta
{

    public static function json(mixed $datos, int $codigo = 200): void
    {

        http_response_code($codigo); //codigo de respuesta http por defecto
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
}
