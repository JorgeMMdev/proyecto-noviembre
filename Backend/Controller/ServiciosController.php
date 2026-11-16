<?php

namespace App\Controller;

use App\DAO\CategoriaDAOImplements;
use App\DAO\ServicioDAOImplements;
use Twig\Environment;

class ServiciosController
{
    public function __construct(private Environment $twig)
    {
    }

    // GET /servicios?q=ingles&categoria=2
    public function index(): void
    {
        $texto = trim((string) filter_input(INPUT_GET, 'q'));
        $idCategoria = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT) ?: null;

        echo $this->twig->render('servicios/index.html.twig', [
            'servicios'   => (new ServicioDAOImplements())->buscar($texto, $idCategoria),
            'categorias'  => (new CategoriaDAOImplements())->listar(),
            'texto'       => $texto,
            'categoriaId' => $idCategoria,
        ]);
    }

    // GET /servicios/5
    public function show(int $id): void
    {
        $servicio = (new ServicioDAOImplements())->buscarPorId($id);

        if ($servicio === null) {
            http_response_code(404);
            echo $this->twig->render('errores/404.html.twig', [
                'codigo'  => 404,
                'mensaje' => 'El servicio no existe.',
            ]);
            return;
        }

        echo $this->twig->render('servicios/show.html.twig', ['servicio' => $servicio]);
    }
}