<?php

namespace App\Controller\Api;

use App\DAO\CategoriaDAOImplements;
use App\Helpers\Respuesta;
use App\Modelo\Categoria;

class CategoriasController
{

    public function index(): void
    {
        $categoriaDAO = new CategoriaDAOImplements();
        $categorias = $categoriaDAO->listar();

        foreach ($categorias as $c) {
            $datos[] = [
                'id'     => $c->getIdCategoria(),
                'nombre' => $c->getNombreCategoria(),
            ];
        }

        Respuesta::json($datos);
    }
}
