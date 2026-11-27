<?php

namespace App\Helpers;

/**
 * Ordenar y buscar en las tablas del panel de admin.
 *
 * Todo va por la URL (GET), sin JS: /usuarios?q=lucia&orden=saldo&dir=desc
 * Así el enlace se puede guardar o recargar y se ve lo mismo.
 */
class Tabla
{
    /**
     * Lee ?orden y ?dir. Solo acepta columnas de la lista $columnas,
     * así nadie puede meter cualquier cosa por la URL.
     *
     * @return array{orden: string, dir: string}
     */
    public static function leerOrden(array $columnas, string $porDefecto, string $dirPorDefecto = 'asc'): array
    {
        $orden = (string) filter_input(INPUT_GET, 'orden');
        $dir = filter_input(INPUT_GET, 'dir') === 'desc' ? 'desc' : 'asc';

        if (!array_key_exists($orden, $columnas)) {
            $orden = $porDefecto;
            $dir = $dirPorDefecto;
        }

        return ['orden' => $orden, 'dir' => $dir];
    }

    /** Texto del buscador (?q=), sin espacios de sobra. */
    public static function leerBusqueda(): string
    {
        return trim((string) filter_input(INPUT_GET, 'q'));
    }

    /**
     * Ordena las filas. $columnas es [nombre => función que saca el valor de una fila].
     * Los números se comparan como números y los textos sin tener en cuenta tildes ni mayúsculas.
     */
    public static function ordenar(array $filas, array $columnas, string $orden, string $dir): array
    {
        $valor = $columnas[$orden];

        usort($filas, function ($a, $b) use ($valor) {
            $x = $valor($a);
            $y = $valor($b);

            // Los vacíos (sin valoración, sin descripción…) siempre al final
            if ($x === null || $y === null) {
                return ($x === null) <=> ($y === null);
            }
            if (is_string($x)) {
                return strcmp(self::normalizar($x), self::normalizar($y));
            }
            return $x <=> $y;
        });

        return $dir === 'desc' ? self::nulosAlFinal(array_reverse($filas), $valor) : $filas;
    }

    /** Se queda con las filas en las que alguno de los textos contiene lo buscado. */
    public static function filtrar(array $filas, string $buscado, callable $textos): array
    {
        if ($buscado === '') {
            return $filas;
        }

        $buscado = self::normalizar($buscado);

        return array_values(array_filter($filas, function ($fila) use ($buscado, $textos) {
            foreach ($textos($fila) as $texto) {
                if (str_contains(self::normalizar((string) $texto), $buscado)) {
                    return true;
                }
            }
            return false;
        }));
    }

    /**
     * Lo que necesita la plantilla para pintar las flechas de las cabeceras.
     * $filtros son los otros parámetros de la URL (q, estado…) para no perderlos al ordenar.
     */
    public static function paraVista(array $orden, array $filtros = []): array
    {
        return [
            'orden'   => $orden['orden'],
            'dir'     => $orden['dir'],
            'filtros' => array_filter($filtros, fn($v) => $v !== null && $v !== ''),
        ];
    }

    /** "Lucía" → "lucia", para que buscar y ordenar no dependan de tildes ni mayúsculas. */
    private static function normalizar(string $texto): string
    {
        return strtr(mb_strtolower($texto), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n~',
        ]);
    }

    /** Al dar la vuelta a la lista, los vacíos quedarían primero: los devolvemos al final. */
    private static function nulosAlFinal(array $filas, callable $valor): array
    {
        $con = array_filter($filas, fn($f) => $valor($f) !== null);
        $sin = array_filter($filas, fn($f) => $valor($f) === null);

        return array_values([...$con, ...$sin]);
    }
}
