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

    // POST /api/categorias  (solo admin)
    public function store(): void
    {
        if (!$this->comprobarAdmin()) {
            return;
        }

        $datos = $this->leerYValidar();
        if ($datos === null) {
            return;
        }

        $categoria = new Categoria(null, $datos['nombre'], $datos['descripcion']);
        (new CategoriaDAOImplements())->insertar($categoria);

        Respuesta::json(["ok" => true, "id" => $categoria->getIdCategoria()], 201);
    }

    // PUT /api/categorias/3  (solo admin)
    public function update(int $id): void
    {
        if (!$this->comprobarAdmin()) {
            return;
        }

        $categoriaDAO = new CategoriaDAOImplements();
        $categoria = $categoriaDAO->buscarPorId($id);
        if ($categoria === null) {
            Respuesta::json(["error" => "La categoría no existe."], 404);
            return;
        }

        $datos = $this->leerYValidar();
        if ($datos === null) {
            return;
        }

        $categoria->setNombreCategoria($datos['nombre']);
        $categoria->setDescripcion($datos['descripcion']);
        $categoriaDAO->actualizar($categoria);

        Respuesta::json(["ok" => true]);
    }

    // DELETE /api/categorias/3  (solo admin, y solo si no tiene servicios)
    public function destroy(int $id): void
    {
        if (!$this->comprobarAdmin()) {
            return;
        }

        $categoriaDAO = new CategoriaDAOImplements();
        if ($categoriaDAO->buscarPorId($id) === null) {
            Respuesta::json(["error" => "La categoría no existe."], 404);
            return;
        }

        // En la BD el borrado va en cascada: borraría sus servicios, sus ofertas
        // y hasta el historial de solicitudes. Por eso no se deja si tiene servicios.
        $servicios = $categoriaDAO->contarServicios($id);
        if ($servicios > 0) {
            Respuesta::json(["error" => "No se puede borrar: tiene $servicios servicio(s). Muévelos o bórralos antes."], 409);
            return;
        }

        $categoriaDAO->borrar($id);
        Respuesta::json(["ok" => true]);
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