<?php

namespace App;

use App\Controller\HomeController;
use App\Helpers\Router;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class App
{
    public static function main(): void
    {
        session_start();

        $twig = new Environment(
            new FilesystemLoader(__DIR__ . '/Templates'),
            ['cache' => false, 'debug' => true]
        );

        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Variables disponibles en todas las plantillas (las usa el menú)
        $twig->addGlobal('usuario', $_SESSION['usuario'] ?? null);
        $twig->addGlobal('ruta', $ruta);

        $router = new Router();
        $home = new HomeController($twig);
        $router->get('/', [$home, 'inicio']);

        if (!$router->despachar($_SERVER['REQUEST_METHOD'] ?? 'GET', $ruta)) {
            http_response_code(404);
            echo $twig->render('errores/404.html.twig');
        }
    }
}