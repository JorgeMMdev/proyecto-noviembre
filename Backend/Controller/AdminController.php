<?php

namespace App\Controller;

use App\Helpers\Acceso;

class AdminController
{
    // GET /admin  → el panel empieza directamente en Categorías
    public function index(): void
    {
        Acceso::exigirAdmin();

        header('Location: /categorias');
        exit;
    }
}