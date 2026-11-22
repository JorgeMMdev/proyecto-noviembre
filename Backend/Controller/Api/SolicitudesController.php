<?php

namespace App\Controller\Api;

use App\DAO\BD;
use App\DAO\OfertaProfesionalDAOImplements;
use App\DAO\SolicitudDAOImplements;
use App\DAO\UsuarioDAOImplements;
use App\Helpers\Respuesta;
use App\Helpers\Sesion;
use App\Modelo\EstadoSolicitud;
use App\Modelo\Solicitud;
use DateTimeImmutable;

class SolicitudesController
{
    // POST /api/solicitudes
    public function store(): void
    {
        // 1. Hay que estar logueado
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

        // 2. La oferta tiene que existir, estar activa y no ser tuya
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

        // 3. Validar fecha y horas
        $errores = [];

        // El input datetime-local envía "2026-11-25T10:30"
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

        // 4. Calcular el precio
        $horas = (float) $horas;
        $precio = (int) ceil($oferta->getPuntosHora() * $horas);

        $solicitud = new Solicitud(
            null,
            $oferta->getIdOferta(),
            $sesion['id'],
            $precio,
            $horas,
            $fecha
        );

        // 5. Cobrar al cliente y crear la solicitud: o las dos cosas, o ninguna
        $pdo = BD::conectar();
        try {
            $pdo->beginTransaction();

            // Resta solo si hay saldo suficiente; si no, no cambia nada y devuelve false
            if (!(new UsuarioDAOImplements())->restarSaldo($sesion['id'], $precio)) {
                $pdo->rollBack();
                Respuesta::json(["errores" => ["saldo" => "No tienes saldo suficiente: este servicio cuesta $precio pts."]], 422);
                return;
            }

            (new SolicitudDAOImplements())->insertar($solicitud);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Respuesta::json(["error" => "No se pudo crear la solicitud."], 500);
            return;
        }

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

        $cambiada = (new SolicitudDAOImplements())->cambiarEstado(
            $id,
            EstadoSolicitud::SOLICITADO,
            EstadoSolicitud::ACEPTADO
        );
        if (!$cambiada) {
            Respuesta::json(["error" => "Esta solicitud ya no se puede aceptar."], 409);
            return;
        }

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

        $this->cerrarYDevolver($fila, EstadoSolicitud::RECHAZADO);
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

        $this->cerrarYDevolver($fila, EstadoSolicitud::CANCELADO);
    }

    // PUT /api/solicitudes/7/confirmar  (cliente o profesional, cuando el servicio está hecho)
    public function putConfirmar(int $id): void
    {
        $fila = $this->cargarSolicitud($id);
        if ($fila === null) {
            return;
        }

        $yo = Sesion::leer('usuario')['id'];
        $comoCliente = (int) $fila['id_solicitante'] === $yo;
        $comoProfesional = (int) $fila['id_profesional'] === $yo;

        if (!$comoCliente && !$comoProfesional) {
            Respuesta::json(["error" => "Esta solicitud no es tuya."], 403);
            return;
        }

        $pdo = BD::conectar();
        $solicitudDAO = new SolicitudDAOImplements();

        try {
            $pdo->beginTransaction();

            // Releer la solicitud BLOQUEADA: si los dos confirman a la vez,
            // la segunda petición espera aquí y ve ya la confirmación de la primera
            $fila = $solicitudDAO->buscarPorId($id, true);

            $estado = $fila['estado'];
            if ($estado !== EstadoSolicitud::ACEPTADO->value && $estado !== EstadoSolicitud::EN_EJECUCION->value) {
                $pdo->rollBack();
                Respuesta::json(["error" => "Esta solicitud no se puede confirmar ahora."], 409);
                return;
            }

            $yaConfirme = $comoCliente ? $fila['confirmado_cliente'] : $fila['confirmado_prestador'];
            $otroConfirmo = $comoCliente ? $fila['confirmado_prestador'] : $fila['confirmado_cliente'];

            if ($yaConfirme) {
                $pdo->rollBack();
                Respuesta::json(["error" => "Ya habías confirmado esta solicitud."], 409);
                return;
            }

            // Si el otro ya había confirmado, con esta confirmación se completa
            $nuevo = $otroConfirmo ? EstadoSolicitud::COMPLETADO : EstadoSolicitud::EN_EJECUCION;
            $solicitudDAO->registrarConfirmacion($id, $comoCliente, $nuevo);

            // Completada: los puntos retenidos llegan por fin al profesional
            if ($nuevo === EstadoSolicitud::COMPLETADO) {
                (new UsuarioDAOImplements())->sumarSaldo((int) $fila['id_profesional'], (int) $fila['precio_final']);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Respuesta::json(["error" => "No se pudo confirmar la solicitud."], 500);
            return;
        }

        Respuesta::json(["ok" => true, "completada" => $nuevo === EstadoSolicitud::COMPLETADO]);
    }

    private function cerrarYDevolver(array $fila, EstadoSolicitud $nuevo): void
    {
        $pdo = BD::conectar();
        try {
            $pdo->beginTransaction();

            // Solo cambia si el estado sigue siendo el que leímos.
            // Si otra petición se adelantó (un doble clic), no devolvemos dos veces.
            $cambiada = (new SolicitudDAOImplements())->cambiarEstado(
                (int) $fila['id_solicitud'],
                EstadoSolicitud::from($fila['estado']),
                $nuevo
            );
            if (!$cambiada) {
                $pdo->rollBack();
                Respuesta::json(["error" => "La solicitud ha cambiado mientras tanto. Recarga la página."], 409);
                return;
            }

            (new UsuarioDAOImplements())->sumarSaldo((int) $fila['id_solicitante'], (int) $fila['precio_final']);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Respuesta::json(["error" => "No se pudo completar la acción."], 500);
            return;
        }

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
