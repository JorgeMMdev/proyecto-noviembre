<?php

namespace App\Helpers;

use Twig\Environment;

//Router por convencion
class Router
{

    private const NAMESPACE_WEB = 'App\\Controller\\';
    private const NAMESPACE_API = 'App\\Controller\\Api\\';
    private const VERBOS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private Environment $twig) {}

    public function dispatch(): void
    {

        $verbo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); // pillo el verbo
        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '/'; // pillo la ruta
        $segmentos = array_values(array_filter(explode('/', strtolower($ruta)))); // segmento la ruta

        // Manolo o silverio 
        $esApi = ($segmentos[0] ?? '') === 'api';
        if ($esApi) {
            array_shift($segmentos); // Array shift se salta el primer valor de $segmentos (el verbo)
            header('Content-Type: application/json'); // cabecera de respuesta
        }

       $recurso = array_shift($segmentos) ?? 'home';     // recurso
        $id = null; // id del recurso
        if ($segmentos !== [] && ctype_digit($segmentos[0])) {  // ctype_digit($segmentos[0]): devuelve true si el texto está formado solo por dígitos. 
            //'5' y '123' dan true, mientras que 'eliminar', '5a', '-3' o '' dan false.
            $id = (int) array_shift($segmentos); //guardo el id porque si no hay que llamar a todos.    
        }

        $accion = array_shift($segmentos); // accion

        // Validar lo que viene de la URL
        if (
            $segmentos !== []
            || !preg_match('/^[a-z][a-z0-9]*$/', $recurso)
            || ($accion !== null && !preg_match('/^[a-z][a-z0-9]*$/', $accion))
        ) {  //preg_match — Realiza una búsqueda de coincidencia con una expresión regular estándar
            $this->error(404, 'Ruta no encontrada', $esApi);
        }
        if (!in_array($verbo, self::VERBOS, true)) {
            $this->error(405, 'Método HTTP no soportado', $esApi);
        }

        $clase = ($esApi ? self::NAMESPACE_API : self::NAMESPACE_WEB) . ucfirst($recurso) . 'Controller'; //ucfist: convierte la primera letra de una cadena a mayúscula
        if (!class_exists($clase)) {
            $this->error(404, 'Recurso no encontrado', $esApi);
        }
        $controller = $esApi ? new $clase() : new $clase($this->twig);

        // Método según verbo y acción
        $metodo = $this->resolverMetodo($verbo, $id, $accion);
        if (!is_callable([$controller, $metodo])) { //is_callable — Determina si un valor puede ser llamado como una función en el ámbito actual
            $this->error(404, 'Ruta no encontrada', $esApi);
        }
        if ($id === null && in_array($metodo, ['update', 'destroy'], true)) {
            $this->error(400, 'Falta el ID en la URL', $esApi);
        }

        if ($id !== null) {
            $params = [$id];   // hay id → lista con un elemento
        } else {
            $params = [];      // no hay id → lista vacía
        }
        $controller->$metodo(...$params); // LA MAGIA
    }


    private function resolverMetodo(string $verbo, ?int $id, ?string $accion): string
    {
        if ($accion !== null) {
            return strtolower($verbo) . ucfirst($accion);   // getLogin, postLogin, putAceptar...
        }

        return match ($verbo) {
            'GET' => $id !== null ? 'show' : 'index',
            'POST' => 'store',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'destroy',
        };
    }
    private function error(int $codigo, string $mensaje, bool $esApi): never
    {
        http_response_code($codigo);

        if ($esApi) {
            echo json_encode(['error' => $mensaje], JSON_UNESCAPED_UNICODE);
        } else {
            echo $this->twig->render('errores/404.html.twig', ['codigo' => $codigo, 'mensaje' => $mensaje]);
        }
        exit;
    }
}
