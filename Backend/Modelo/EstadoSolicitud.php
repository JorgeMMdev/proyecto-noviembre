<?php

namespace App\Modelo;

enum EstadoSolicitud: string
{
    case SOLICITADO = 'SOLICITADO';
    case ACEPTADO = 'ACEPTADO';
    case RECHAZADO = 'RECHAZADO';
    case EN_EJECUCION = 'EN_EJECUCION';
    case COMPLETADO = 'COMPLETADO';
    case CANCELADO = 'CANCELADO';
}