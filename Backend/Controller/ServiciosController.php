<?php

namespace App\Controller\Api;

use App\DAO\CategoriaDAOImplements;
use App\DAO\ServicioDAOImplements;
use App\Helpers\Acceso;
use App\Helpers\Respuesta;
use App\Modelo\Servicio;

class ServiciosController
{
    // GET /api/servicios            → todos (panel de admin)
    // GET /api/servicios?categoria=2 → los de una categoría (registro)
    public function index(): void
    {
        // null si no viene el parámetro; false si viene pero no es un número
        $idCategoria = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);

        if ($idCategoria === false) {
            Respuesta::json(['error' => 'Categoría no válida'], 400);
            return;
        }

        $servicioDAO = new ServicioDAOImplements();
        $datos = [];

        foreach ($servicioDAO->buscar(null, $idCategoria) as $s) {
            $datos[] = [
                'id'          => $s->getIdServicio(),
                'idCategoria' => $s->getIdCategoria(),
                'nombre'      => $s->getNombreServicio(),
                'precio'      => $s->getPrecioMedioEstimado(),
                'tiempo'      => $s->getTiempoMedioEstimado(),
                'descripcion' => $s->getDescripcion(),
                'ofertas'     => $servicioDAO->contarOfertas($s->getIdServicio()),
            ];
        }

        Respuesta::json($datos);
    }

    // POST /api/servicios  (solo admin)
    public function store(): void
    {
        if (!Acceso::soloAdmin()) {
            return;
        }

        $datos = $this->leerYValidar();
        if ($datos === null) {
            return;
        }

        $servicio = new Servicio(
            null,
            $datos['idCategoria'],
            $datos['nombre'],
            $datos['precio'],
            $datos['tiempo'],
            $datos['descripcion']
        );
        (new ServicioDAOImplements())->insertar($servicio);

        Respuesta::json(["ok" => true, "id" => $servicio->getIdServicio()], 201);
    }

    // PUT /api/servicios/4  (solo admin)
    public function update(int $id): void
    {
        if (!Acceso::soloAdmin()) {
            return;
        }

        $servicioDAO = new ServicioDAOImplements();
        $servicio = $servicioDAO->buscarPorId($id);
        if ($servicio === null) {
            Respuesta::json(["error" => "El servicio no existe."], 404);
            return;
        }

        $datos = $this->leerYValidar();
        if ($datos === null) {
            return;
        }

        $servicio->setIdCategoria($datos['idCategoria']);
        $servicio->setNombreServicio($datos['nombre']);
        $servicio->setPrecioMedioEstimado($datos['precio']);
        $servicio->setTiempoMedioEstimado($datos['tiempo']);
        $servicio->setDescripcion($datos['descripcion']);
        $servicioDAO->actualizar($servicio);

        Respuesta::json(["ok" => true]);
    }

    // DELETE /api/servicios/4  (solo admin, y solo si nadie lo ofrece)
    public function destroy(int $id): void
    {
        if (!Acceso::soloAdmin()) {
            return;
        }

        $servicioDAO = new ServicioDAOImplements();
        if ($servicioDAO->buscarPorId($id) === null) {
            Respuesta::json(["error" => "El servicio no existe."], 404);
            return;
        }

        // Borrar en cascada se llevaría las ofertas y el historial de solicitudes
        $ofertas = $servicioDAO->contarOfertas($id);
        if ($ofertas > 0) {
            Respuesta::json(["error" => "No se puede borrar: lo ofrecen $ofertas profesional(es)."], 409);
            return;
        }

        $servicioDAO->borrar($id);
        Respuesta::json(["ok" => true]);
    }

    /** Lee el JSON y lo valida. Si algo falla, ya responde con el error y devuelve null. */
    private function leerYValidar(): ?array
    {
        $datos = json_decode(file_get_contents("php://input"), true);
        if (!is_array($datos)) {
            Respuesta::json(["error" => "Datos no válidos"], 400);
            return null;
        }

        $errores = [];

        $idCategoria = $datos["idCategoria"] ?? null;
        if (!is_int($idCategoria) || (new CategoriaDAOImplements())->buscarPorId($idCategoria) === null) {
            $errores["idCategoria"] = "Elige una categoría.";
        }

        $nombre = trim((string) ($datos["nombre"] ?? ""));
        if ($nombre === "" || mb_strlen($nombre) > 150) {
            $errores["nombre"] = "El nombre es obligatorio y no puede pasar de 150 caracteres.";
        }

        $precio = $datos["precio"] ?? null;
        if (!is_int($precio) || $precio < 1 || $precio > 100) {
            $errores["precio"] = "El precio de referencia debe estar entre 1 y 100 pts/h.";
        }

        $tiempo = $datos["tiempo"] ?? null;
        if (!is_numeric($tiempo) || $tiempo < 0.5 || $tiempo > 24 || fmod((float) $tiempo, 0.5) != 0) {
            $errores["tiempo"] = "El tiempo estimado debe ir de 0,5 a 24 h, en tramos de media hora.";
        }

        $descripcion = trim((string) ($datos["descripcion"] ?? ""));
        if (mb_strlen($descripcion) > 1000) {
            $errores["descripcion"] = "La descripción no puede pasar de 1000 caracteres.";
        }

        if (!empty($errores)) {
            Respuesta::json(["errores" => $errores], 422);
            return null;
        }

        return [
            'idCategoria' => $idCategoria,
            'nombre'      => $nombre,
            'precio'      => $precio,
            'tiempo'      => (float) $tiempo,
            'descripcion' => $descripcion === "" ? null : $descripcion,
        ];
    }
}