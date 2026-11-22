<?php

declare(strict_types=1);

namespace App\Controller;

use App\DAO\UsuarioDAOImplements;
use App\Helpers\Autenticacion;
use App\Helpers\Sesion;
use Twig\Environment;

final class AuthController
{
    private Autenticacion $auth;

    public function __construct(private Environment $twig)
    {
        $this->auth = new Autenticacion(new UsuarioDAOImplements());
    }

    // ---------- Paso 1: correo y contraseña ----------

    // GET /auth/login
    public function getLogin(): void
    {
        $this->pintarLogin();
    }

    // POST /auth/login
    public function postLogin(): void
    {
        $correo = trim((string) filter_input(INPUT_POST, 'correo'));
        $contrasena = (string) filter_input(INPUT_POST, 'contrasena');

        $usuario = $this->auth->verificarCredenciales($correo, $contrasena);

        // El mismo mensaje si no existe el correo o si falla la contraseña:
        // así no se puede averiguar qué correos están registrados
        if ($usuario === null) {
            $this->pintarLogin($correo, "Correo o contraseña incorrectos.");
            return;
        }

        if (!$this->auth->enviarCodigo($usuario)) {
            $this->pintarLogin($correo, "No se pudo enviar el código. Inténtalo de nuevo.");
            return;
        }

        // Redirección tras el POST: al recargar no se vuelve a enviar el formulario
        header('Location: /auth/codigo');
        exit;
    }

    // ---------- Paso 2: código del correo ----------

    // GET /auth/codigo
    public function getCodigo(): void
    {
        if (!$this->auth->hayCodigoPendiente()) {
            header('Location: /auth/login');
            exit;
        }

        $this->pintarCodigo();
    }

    // POST /auth/codigo
    public function postCodigo(): void
    {
        $error = $this->auth->verificarCodigo((string) filter_input(INPUT_POST, 'codigo'));

        if ($error === null) {
            header('Location: /');
            exit;
        }

        // Si el intento ya no vale (caducado o demasiados fallos), vuelta al paso 1
        if (!$this->auth->hayCodigoPendiente()) {
            $this->pintarLogin('', $error);
            return;
        }

        $this->pintarCodigo($error);
    }

    // ---------- Logout ----------

    // POST /auth/logout
    public function postLogout(): void
    {
        $this->auth->logout();
        header('Location: /');
        exit;
    }

    // ---------- Ayudantes ----------

    private function pintarLogin(string $correo = '', ?string $error = null): void
    {
        // Si ya hay sesión, no tiene sentido ver el login
        if (Sesion::leer('usuario') !== null) {
            header('Location: /');
            exit;
        }

        echo $this->twig->render('auth/login.html.twig', [
            'correo' => $correo,
            'error'  => $error,
        ]);
    }

    private function pintarCodigo(?string $error = null): void
    {
        echo $this->twig->render('auth/codigo.html.twig', [
            'correoDestino' => $this->auth->correoPendiente(),
            'error'         => $error,
        ]);
    }
}