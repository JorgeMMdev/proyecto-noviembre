<?php

namespace App\Controller\Api;

use App\DAO\OfertaProfesionalDAOImplements;
use App\DAO\SolicitudDAOImplements;
use App\DAO\UsuarioDAOImplements;
use App\Helpers\Respuesta;
use App\Helpers\Sesion;
use App\Modelo\Solicitud;
use DateTimeImmutable;
use App\Modelo\EstadoSolicitud;

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

    // PUT /api/solicitudes/7/aceptar  (solo el profesional, solo si está SOLICITADO)
    public function putAceptar(int $id): void
    {
        $fila = $this->cargarSolicitud($id);
        if ($fila === null) {
            return;
        }

        if ((int) $fila['id_profesional'] !== Sesion::leer('usuario')['id']) {
            Respuesta::json(["error" => "Solo el profesional puede aceptar la solicitud."], 403);
            return;
        }
        if ($fila['estado'] !== EstadoSolicitud::SOLICITADO->value) {
            Respuesta::json(["error" => "Esta solicitud ya no se puede aceptar."], 409);
            return;
        }

        (new SolicitudDAOImplements())->cambiarEstado($id, EstadoSolicitud::ACEPTADO);
        Respuesta::json(["ok" => true]);
    }

    // PUT /api/solicitudes/7/rechazar  (solo el profesional, solo si está SOLICITADO)
    public function putRechazar(int $id): void
    {
        $fila = $this->cargarSolicitud($id);
        if ($fila === null) {
            return;
        }

        if ((int) $fila['id_profesional'] !== Sesion::leer('usuario')['id']) {
            Respuesta::json(["error" => "Solo el profesional puede rechazar la solicitud."], 403);
            return;
        }
        if ($fila['estado'] !== EstadoSolicitud::SOLICITADO->value) {
            Respuesta::json(["error" => "Esta solicitud ya no se puede rechazar."], 409);
            return;
        }

        (new SolicitudDAOImplements())->cambiarEstado($id, EstadoSolicitud::RECHAZADO);
        Respuesta::json(["ok" => true]);
    }

    // PUT /api/solicitudes/7/cancelar  (solo el cliente, mientras nadie haya confirmado)
    public function putCancelar(int $id): void
    {
        $fila = $this->cargarSolicitud($id);
        if ($fila === null) {
            return;
        }

        if ((int) $fila['id_solicitante'] !== Sesion::leer('usuario')['id']) {
            Respuesta::json(["error" => "Solo quien pidió el servicio puede cancelarlo."], 403);
            return;
        }

        $cancelable = $fila['estado'] === EstadoSolicitud::SOLICITADO->value
            || ($fila['estado'] === EstadoSolicitud::ACEPTADO->value
                && !$fila['confirmado_cliente'] && !$fila['confirmado_prestador']);

        if (!$cancelable) {
            Respuesta::json(["error" => "Esta solicitud ya no se puede cancelar."], 409);
            return;
        }

        (new SolicitudDAOImplements())->cambiarEstado($id, EstadoSolicitud::CANCELADO);
        Respuesta::json(["ok" => true]);
    }

    /**
     * Comprueba la sesión y que la solicitud existe.
     * Si algo falla, ya responde con el error y devuelve null.
     */
    private function cargarSolicitud(int $id): ?array
    {
        if (Sesion::leer('usuario') === null) {
            Respuesta::json(["error" => "Debes iniciar sesión."], 401);
            return null;
        }

        $fila = (new SolicitudDAOImplements())->buscarPorId($id);
        if ($fila === null) {
            Respuesta::json(["error" => "La solicitud no existe."], 404);
            return null;
        }

        return $fila;
    }
}
