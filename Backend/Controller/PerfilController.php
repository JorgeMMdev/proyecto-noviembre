<?php

namespace App\Controller;

use App\DAO\UsuarioDAOImplements;
use App\DAO\OfertaProfesionalDAOImplements;
use App\Helpers\Sesion;
use Twig\Environment;

class PerfilController
{

    public function __construct(private Environment $twig) {}

    public function index(): void
    {

        $sesion = Sesion::leer('usuario');
        if ($sesion == null) {
            header('Location: auth/login');
            exit;
        }


        $perfil = (new UsuarioDAOImplements())->buscarPorId($sesion['id']);
        if ($perfil === null) {
            header('Location: auth/login');
            exit;
        }

        $usuarioDAO = new UsuarioDAOImplements();
        $perfil = $usuarioDAO->buscarPorId($sesion['id']);

        echo $this->twig->render('perfil/index.html.twig', [
            'perfil'  => $perfil,
            'stats'   => $usuarioDAO->estadisticas($perfil->getIdUsuario()),
            'ofertas' => (new OfertaProfesionalDAOImplements())->listarPorUsuario($perfil->getIdUsuario()),
        ]);
    }
}
