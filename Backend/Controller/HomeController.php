<?php
declare(strict_types=1);

namespace App\Controller;

use App\DAO\BD;
use Twig\Environment;

final class HomeController
{
    public function __construct(private Environment $twig)
    {
    }

    public function inicio(): void
    {
        // Prueba de conexión (quitar cuando haya DAO reales)
        try {
            $total = (int) BD::conectar()->query('SELECT COUNT(*) FROM categorias')->fetchColumn();
            $estadoBD = "Conexión correcta: {$total} categorías en la base de datos.";
        } catch (\Throwable $e) {
            $estadoBD = 'Error de base de datos: ' . $e->getMessage();
        }

        echo $this->twig->render('inicio.html.twig', ['estadoBD' => $estadoBD]);
    }
}