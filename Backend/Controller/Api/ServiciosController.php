<?php

namespace App\Controller\Api;

use App\DAO\ServicioDAOImplements;
use App\Helpers\Respuesta;

class ServiciosController
{

    public function index(): void
    {
        $idCategoria = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);

        if ($idCategoria === null || $idCategoria === false) {

            Respuesta::json(['error' => 'Categoría no válida'], 400);
            return;
        }

        $servcios = (new ServicioDAOImplements())->buscar(null, $idCategoria);

        $datos = [];

        foreach ($servcios as $s) {
            $datos[] = [
                'id'     => $s->getIdServicio(),
                'nombre' => $s->getNombreServicio(),
                'descripcion' => $s->getDescripcion()
            ];
        }

        Respuesta::json($datos);
    }
}
