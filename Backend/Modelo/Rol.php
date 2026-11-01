<?php

namespace App\Modelo;

/* 
PROMPT: ¿Qué es un enum en programación?

RESPUESTA:
Un enum (enumeración) es un tipo de dato que solo puede tomar un valor
de una lista cerrada que defines tú. Es como decir: “esta variable solo
puede ser una de estas opciones, y ninguna más”.
*/ 

enum Rol: string {
    case ADMIN = 'admin';
    case PROFESIONAL = 'profesional';
}