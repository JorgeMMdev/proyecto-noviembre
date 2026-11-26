<?php

namespace App\Controller\Api;

use App\DAO\CategoriaDAOImplements;
use App\Helpers\Respuesta;
use App\Helpers\Sesion;
use App\Modelo\Categoria;

class CategoriasController
{
    // GET /api/categorias  (pública: la usan el registro y el panel de admin)
 public function index(): void
{
    $categoriaDAO = new CategoriaDAOImplements();
    $datos = [];

    foreach ($categoriaDAO->listar() as $c) {
        $datos[] = [
            'id'          => $c->getIdCategoria(),
            'nombre'      => $c->getNombreCategoria(),
            'descripcion' => $c->getDescripcion(),
            'servicios'   => $categoriaDAO->contarServicios($c->getIdCategoria()),
        ];
    }

    Respuesta::json($datos);
}

    private function comprobarAdmin(): bool
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

    /** Lee el JSON y lo valida. Si algo falla, ya responde con el error y devuelve null. */
    private function leerYValidar(): ?array
    {
        $datos = json_decode(file_get_contents("php://input"), true);
        if (!is_array($datos)) {
            Respuesta::json(["error" => "Datos no válidos"], 400);
            return null;
        }

        $nombre = trim((string) ($datos["nombre"] ?? ""));
        $descripcion = trim((string) ($datos["descripcion"] ?? ""));

        $errores = [];
        if ($nombre === "" || mb_strlen($nombre) > 100) {
            $errores["nombre"] = "El nombre es obligatorio y no puede pasar de 100 caracteres.";
        }
        if (mb_strlen($descripcion) > 1000) {
            $errores["descripcion"] = "La descripción no puede pasar de 1000 caracteres.";
        }

        if (!empty($errores)) {
            Respuesta::json(["errores" => $errores], 422);
            return null;
        }

        return ['nombre' => $nombre, 'descripcion' => $descripcion === "" ? null : $descripcion];
    }
}