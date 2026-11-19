<?php

namespace App\Controller\Api;

use App\DAO\OfertaProfesionalDAOImplements;
use App\DAO\SolicitudDAOImplements;
use App\DAO\UsuarioDAOImplements;
use App\Helpers\Respuesta;
use App\Helpers\Sesion;
use App\Modelo\Solicitud;
use DateTimeImmutable;

class SolicitudesController
{
    public function store(): void
    {
        $sesion = Sesion::leer('usuario');
        if ($sesion === null) {
            Respuesta::json(["error" => "Debes iniciar sesión."], 401);
            return;
        }

        $datos = json_decode(file_get_contents("php://input"), true);
        if (!is_array($datos)) {
            Respuesta::json(["error" => "Datos no válidos"], 400);
            return;
        }

        $idOferta = $datos["idOferta"] ?? null;
        $oferta = is_int($idOferta) ? (new OfertaProfesionalDAOImplements())->buscarPorId($idOferta) : null;

        if ($oferta === null || !$oferta->isActivo()) {
            Respuesta::json(["error" => "Esa oferta no está disponible."], 404);
            return;
        }
        if ($oferta->getIdUsuario() === $sesion['id']) {
            Respuesta::json(["error" => "No puedes solicitar tu propio servicio."], 422);
            return;
        }

        $errores = [];

        $fecha = DateTimeImmutable::createFromFormat("Y-m-d\TH:i", $datos["fecha"] ?? "");
        if ($fecha === false) {
            $errores["fecha"] = "La fecha no es válida.";
        } elseif ($fecha <= new DateTimeImmutable()) {
            $errores["fecha"] = "La fecha tiene que ser posterior a ahora.";
        }

        $horas = $datos["horas"] ?? null;
        if (!is_numeric($horas) || $horas < 0.5 || $horas > 8 || fmod((float) $horas, 0.5) != 0) {
            $errores["horas"] = "Las horas deben ir de 0,5 a 8, en tramos de media hora.";
        }

        if (!empty($errores)) {
            Respuesta::json(["errores" => $errores], 422);
            return;
        }

        $horas = (float) $horas;
        $precio = (int) ceil($oferta->getPuntosHora() * $horas);

        $cliente = (new UsuarioDAOImplements())->buscarPorId($sesion['id']);
        if ($cliente === null) {
            Respuesta::json(["error" => "Tu usuario ya no existe."], 401);
            return;
        }
        if ($cliente->getSaldo() < $precio) {
            Respuesta::json(["errores" => ["saldo" => "No tienes saldo suficiente: cuesta $precio pts y tienes {$cliente->getSaldo()} pts."]], 422);
            return;
        }

        $solicitud = new Solicitud(
            null,
            $oferta->getIdOferta(),
            $cliente->getIdUsuario(),
            $precio,
            $horas,
            $fecha
        );
        (new SolicitudDAOImplements())->insertar($solicitud);

        Respuesta::json(["ok" => true, "precio" => $precio], 201);
    }
}