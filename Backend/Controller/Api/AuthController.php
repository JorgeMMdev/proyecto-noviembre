<?php

namespace App\Controller\Api;

use App\DAO\BD;
use App\DAO\OfertaProfesionalDAOImplements;
use App\DAO\UsuarioDAOImplements;
use App\Helpers\Autenticacion;
use App\Helpers\Respuesta;
use App\Modelo\Usuario;
use DateTime;

class AuthController
{
    private const SALDO_BIENVENIDA = 50;

    // POST /api/auth/registro
    public function postRegistro(): void
    {
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!is_array($datos)) {
            Respuesta::json(["error" => "Datos no válidos"], 400);
            return;
        }

        $errores = $this->validar($datos);
        if (!empty($errores)) {
            Respuesta::json(["errores" => $errores], 422);
            return;
        }

        // Comprobar si el correo ya está registrado
        $correo = strtolower(trim($datos["email"]));
        $usuarioDAO = new UsuarioDAOImplements();

        if ($usuarioDAO->buscarPorCorreo($correo) !== null) {
            Respuesta::json(["errores" => ["email" => "Ese correo ya está registrado."]], 409);
            return;
        }

        $usuario = new Usuario(
            0,
            trim($datos["nombre"]),
            trim($datos["apellidos"]),
            $correo,
            password_hash($datos["contrasena"], PASSWORD_DEFAULT),
            new DateTime($datos["nacimiento"])
        );

        $usuario->setSaldo(self::SALDO_BIENVENIDA);

        $pdo = BD::conectar();
        $ofertaDAO = new OfertaProfesionalDAOImplements();

        try {
            $pdo->beginTransaction();

            $usuarioDAO->insertar($usuario);

            foreach (array_unique($datos["servicios"]) as $idServicio) { // array_unique para quitar repetidos
                $creada = $ofertaDAO->crearDesdeServicio($usuario->getIdUsuario(), $idServicio);

                if (!$creada) {
                    $pdo->rollBack();
                    Respuesta::json(["errores" => ["servicios" => "Alguno de los servicios no existe."]], 422);
                    return;
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            // Algo falló de forma inesperada: deshacer y responder 500
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Respuesta::json(["error" => "No se pudo completar el registro."], 500);
            return;
        }

        $auth = new Autenticacion($usuarioDAO);
        $auth->iniciarSesion($usuario);
        Respuesta::json(["ok" => true, "id" => $usuario->getIdUsuario()], 201);
    }

    private function validar(array $d): array
    {
        $errores = [];

        if (trim($d["nombre"] ?? "") === "") {
            $errores["nombre"] = "El nombre es obligatorio.";
        }
        if (trim($d["apellidos"] ?? "") === "") {
            $errores["apellidos"] = "Los apellidos son obligatorios.";
        }

        $email = strtolower(trim($d["email"] ?? ""));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores["email"] = "El correo no es válido.";
        }

        if (strlen($d["contrasena"] ?? "") < 8) {
            $errores["contrasena"] = "La contraseña debe tener al menos 8 caracteres.";
        }

        $servicios = $d["servicios"] ?? null;
        if (!is_array($servicios) || count($servicios) === 0) {
            $errores["servicios"] = "Elige al menos un servicio.";
        } else {
            foreach ($servicios as $id) {
                if (!is_int($id)) {
                    $errores["servicios"] = "Los servicios no son válidos.";
                    break;
                }
            }
        }

        $fecha = DateTime::createFromFormat("!Y-m-d", $d["nacimiento"] ?? "");
        if ($fecha === false) {
            $errores["nacimiento"] = "La fecha no es válida.";
        } elseif ($fecha > new DateTime("today")) {
            $errores["nacimiento"] = "La fecha no puede ser futura.";
        } elseif ($fecha > new DateTime("today -18 years")) {
            $errores["nacimiento"] = "Debes tener al menos 18 años para registrarte.";
        }

        return $errores;
    }
}