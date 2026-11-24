<?php

namespace App\Helpers;

/**
 * Margen permitido para la tarifa de una oferta (punto 3.2 del enunciado):
 * entre la mitad y el doble del precio de referencia del servicio.
 */
class Tarifa
{
    public static function minima(int $referencia): int
    {
        return max(1, (int) ceil($referencia / 2));
    }

    public static function maxima(int $referencia): int
    {
        return $referencia * 2;
    }

    public static function permitida(int $puntos, int $referencia): bool
    {
        return $puntos >= self::minima($referencia) && $puntos <= self::maxima($referencia);
    }
}