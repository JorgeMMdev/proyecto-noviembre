<?php

namespace App\Controller;

use App\Helpers\Sesion;
use Twig\Environment;

class AdminController
{
    public function __construct(private Environment $twig) {}

    // GET /admin
    public function index(): void
    {
        // Solo el administrador. Sin sesión, al login; con sesión de otro rol, al inicio
        if (Sesion::leer('usuario') === null) {
            header('Location: /auth/login');
            exit;
        }
        if (!Sesion::esAdmin()) {
            header('Location: /');
            exit;
        }

        // La página solo es el "esqueleto": cada pestaña carga sus datos con fetch
        echo $this->twig->render('admin/index.html.twig');
    }
}
