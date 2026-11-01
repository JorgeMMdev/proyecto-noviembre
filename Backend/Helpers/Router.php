<?php
declare(strict_types=1);

namespace App\Helpers;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $rutas = [];

    public function get(string $ruta, callable $accion): void
    {
        $this->rutas['GET'][$ruta] = $accion;
    }

    public function post(string $ruta, callable $accion): void
    {
        $this->rutas['POST'][$ruta] = $accion;
    }

    /** Ejecuta la acción de la ruta. Devuelve false si no existe. */
    public function despachar(string $metodo, string $ruta): bool
    {
        $ruta = rtrim($ruta, '/') ?: '/';
        $accion = $this->rutas[$metodo][$ruta] ?? null;

        if ($accion === null) {
            return false;
        }

        $accion();
        return true;
    }
}