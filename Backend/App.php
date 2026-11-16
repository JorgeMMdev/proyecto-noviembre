<?php
namespace App;

use App\Helpers\Router;
use App\Helpers\Sesion;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class App
{
    public static function main(): void
    {
        // La sesión se arranca aquí, antes de leer $_SESSION
        Sesion::iniciar();

        $twig = new Environment(
            new FilesystemLoader(__DIR__ . '/Templates'),
            ['cache' => false, 'debug' => true]
        );

        // Variables globales para las plantillas de Twig
        $twig->addGlobal('usuario', $_SESSION['usuario'] ?? null);
        $twig->addGlobal('ruta', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

        $router = new Router($twig);
        $router->dispatch();
    }
}