<?php

namespace App\Controller;

use App\DAO\CategoriaDAOImplements;
use Twig\Environment;

class HomeController
{
    public function __construct(private Environment $twig) {}

    public function index(): void
    {
        echo $this->twig->render('inicio.html.twig', [
            'categorias' => (new CategoriaDAOImplements())->listar(),
        ]);
    }
}
