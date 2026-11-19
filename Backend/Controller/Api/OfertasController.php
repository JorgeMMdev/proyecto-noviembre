<?php

namespace App\Controller\Api;


use App\DAO\OfertaProfesionalDAOImplements;
use App\Helpers\Respuesta;
use App\Helpers\Sesion;

class OfertasController
{
    public function update(int $id): void
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

        //La oferta tiene que existir y ser del usuario que la edita
        $ofertaDAO = new OfertaProfesionalDAOImplements();
        $oferta = $ofertaDAO->buscarPorId($id);

        if ($oferta === null) {
            Respuesta::json(["error" => "La oferta no existe."], 404);
            return;
        }
        if ($oferta->getIdUsuario() !== $sesion['id']) {
            Respuesta::json(["error" => "No puedes modificar esta oferta."], 403);
            return;
        }

    
        $errores = [];

        $puntos = $datos["puntosHora"] ?? null;
        if (!is_int($puntos) || $puntos < 1 || $puntos > 100) {
            $errores["puntosHora"] = "Los puntos por hora deben estar entre 1 y 100.";
        }

        $descripcion = trim((string) ($datos["descripcion"] ?? ""));
        if (mb_strlen($descripcion) > 500) {
            $errores["descripcion"] = "La descripción no puede pasar de 500 caracteres.";
        }

        $activo = $datos["activo"] ?? null;
        if (!is_bool($activo)) {
            $errores["activo"] = "El estado de la oferta no es válido.";
        }

        if (!empty($errores)) {
            Respuesta::json(["errores" => $errores], 422);
            return;
        }

  
        $oferta->setPuntosHora($puntos);
        $oferta->setDescripcionPersonal($descripcion === "" ? null : $descripcion);
        $oferta->setActivo($activo);
        $ofertaDAO->actualizar($oferta);

        Respuesta::json(["ok" => true]);
    }
}