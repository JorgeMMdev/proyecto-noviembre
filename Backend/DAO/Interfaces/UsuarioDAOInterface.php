<?php

namespace App\DAO\Interfaces;

use App\Modelo\Usuario;

// Las interfaces son como contratos que definen los métodos que una clase debe implementar.
// En este caso, la interfaz UsuarioDAOinterface define los métodos que cualquier clase que 
//implemente esta interfaz debe tener para interactuar con los datos de usuario en la base de datos.
interface UsuarioDAOinterface
{
    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorCorreo(string $correo): ?Usuario;

    public function insertar(Usuario $usuario): Usuario;

    public function estadisticas(int $idUsuario): array;

    public function restarSaldo(int $idUsuario, int $puntos): bool;

    public function sumarSaldo(int $idUsuario, int $puntos): void;
}
