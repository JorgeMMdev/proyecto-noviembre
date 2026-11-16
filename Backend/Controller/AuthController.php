<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helpers\Autenticacion;
use Twig\Environment;
use App\DAO\UsuarioDAOImplements;

final class AuthController
{
    private Autenticacion $auth;
    public function __construct(private Environment $twig)
    {

        $this->auth = new Autenticacion(new UsuarioDAOImplements());
    }

    public function getLogin(): void
    {
        echo $this->twig->render('auth/login.html.twig', [
            'correo' => '',     // valor para rellenar el campo si hay error
            'error' => null,    // mensaje de error (string) o null
        ]);
    }

    // REGISTRO


    public function getRegistro(): void
    {
        echo $this->twig->render('auth/registro.html.twig');
    }


    public function postRegistro(): void {}

    // LOGOUT
    public function postLogout(): void
    {
        $this->auth->logout();
        header('Location: /');
        exit;
    }
}
