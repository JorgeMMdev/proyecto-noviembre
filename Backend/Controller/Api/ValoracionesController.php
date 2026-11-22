<?php

namespace App\Controller\Api;

use App\DAO\BD;
use App\DAO\SolicitudDAOImplements;
use App\DAO\UsuarioDAOImplements;
use App\DAO\ValoracionDAOImplements;
use App\Helpers\Respuesta;
use App\Helpers\Sesion;
use App\Modelo\EstadoSolicitud;
use App\Modelo\Valoracion;
use PDOException;

class ValoracionesController
{
    // POST /api/valoraciones
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

        $idSolicitud = $datos["idSolicitud"] ?? null;
        $fila = is_int($idSolicitud) ? (new SolicitudDAOImplements())->buscarPorId($idSolicitud) : null;

        if ($fila === null) {
            Respuesta::json(["error" => "La solicitud no existe."], 404);
            return;
        }
        if ((int) $fila['id_solicitante'] !== $sesion['id']) {
            Respuesta::json(["error" => "Solo quien recibió el servicio puede valorarlo."], 403);
            return;
        }
        if ($fila['estado'] !== EstadoSolicitud::COMPLETADO->value) {
            Respuesta::json(["error" => "Solo se pueden valorar servicios completados."], 409);
            return;
        }

        //Validar
        $errores = [];

        $puntuacion = $datos["puntuacion"] ?? null;
        if (!is_int($puntuacion) || $puntuacion < 1 || $puntuacion > 5) {
            $errores["puntuacion"] = "Elige de 1 a 5 estrellas.";
        }

        $comentario = trim((string) ($datos["comentario"] ?? ""));
        if (mb_strlen($comentario) > 500) {
            $errores["comentario"] = "El comentario no puede pasar de 500 caracteres.";
        }

        if (!empty($errores)) {
            Respuesta::json(["errores" => $errores], 422);
            return;
        }

        //  Guardar la valoración y recalcular la media del profesional
        $valoracion = new Valoracion(null, $idSolicitud, $puntuacion, $comentario === "" ? null : $comentario);

        $pdo = BD::conectar();
        try {
            $pdo->beginTransaction();

            (new ValoracionDAOImplements())->insertar($valoracion);
            (new UsuarioDAOImplements())->actualizarValoracionMedia((int) $fila['id_profesional']);

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // 23000 = violación de restricción: el UNIQUE de id_solicitud, ya estaba valorada
            if ($e->getCode() === '23000') {
                Respuesta::json(["error" => "Ya has valorado este servicio."], 409);
                return;
            }
            Respuesta::json(["error" => "No se pudo guardar la valoración."], 500);
            return;
        }

        Respuesta::json(["ok" => true], 201);
    }
}