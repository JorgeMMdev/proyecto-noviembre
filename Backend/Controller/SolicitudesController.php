<?php

namespace App\Controller;

use App\DAO\SolicitudDAOImplements;
use App\Helpers\Sesion;
use Twig\Environment;

class SolicitudesController
{
    public function __construct(private Environment $twig)
    {
    }

    // GET /solicitudes
    public function index(): void
    {
        $sesion = Sesion::leer('usuario');
        if ($sesion === null) {
            header('Location: /auth/login');
            exit;
        }

        $solicitudDAO = new SolicitudDAOImplements();

        echo $this->twig->render('solicitudes/index.html.twig', [
            'recibidas' => $solicitudDAO->listarRecibidas($sesion['id']),
            'enviadas'  => $solicitudDAO->listarEnviadas($sesion['id']),
        ]);
    }
}