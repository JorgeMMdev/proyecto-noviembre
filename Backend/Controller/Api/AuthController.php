<?php

namespace App\Controller\Api;

use App\DAO\UsuarioDAOImplements;
use App\DAO\OfertaProfesionalDAOImplements;
use App\DAO\BD;
use App\Helpers\Sesion;
use App\Service\MailService;
use App\Modelo\Usuario;
use App\Helpers\Respuesta;
use App\Helpers\Autenticacion;
use DateTime;


class AuthController
{

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

        //Comprobar si el correo ya esta registrado
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

        $usuario->setSaldo(50);

        $pdo = BD::conectar();
        $ofertaDAO = new OfertaProfesionalDAOImplements();

        try {
            $pdo->beginTransaction();

            $usuarioDAO->insertar($usuario);

            foreach (array_unique($datos["servicios"]) as $idServicio) { //array_unique para quitar repetidos  
                $creada = $ofertaDAO->crearDesdeServicio($usuario->getIdUsuario(), $idServicio);

                if (!$creada) {
                    $pdo->rollBack();
                    Respuesta::json(["errores" => ["servicios" => "Alguno de los servicios no existe."]], 422);
                    return;
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
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

    public function postLogin(): void
    {
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!is_array($datos)) {
            Respuesta::json(["error" => "Datos no válidos"], 400);
            return;
        }

        $usuarioDAO = new UsuarioDAOImplements();
        $auth = new Autenticacion($usuarioDAO);
        $usuario = $auth->verificarCredenciales($datos["email"] ?? "", $datos["contrasena"] ?? "");

        if ($usuario === null) {
            Respuesta::json(["error" => "Correo o contraseña incorrectos."], 401);
            return;
        }

        //Cogio random
        $codigo = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);

        // 4. Guardar el intento pendiente en la sesión (todavía NO hay login)
        Sesion::escribir('2fa', [
            'id'       => $usuario->getIdUsuario(),
            'hash'     => password_hash($codigo, PASSWORD_DEFAULT),
            'expira'   => time() + 300,
            'intentos' => 0,
        ]);

        if (!MailService::desdeEntorno()->enviarCodigoLogin($usuario, $codigo)) {
            Respuesta::json(["error" => "No se pudo enviar el código. Inténtalo de nuevo."], 500);
            return;
        }

        Respuesta::json(["ok" => true]);
    }

    public function postVerificar(): void
    {
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!is_array($datos)) {
            Respuesta::json(["error" => "Datos no válidos"], 400);
            return;
        }

        $codigo = trim((string) ($datos["codigo"] ?? ""));

        // 1. ¿Hay un inicio de sesión pendiente?
        $pendiente = Sesion::leer('2fa');
        if (!is_array($pendiente)) {
            Respuesta::json(["error" => "No hay ningún inicio de sesión pendiente."], 401);
            return;
        }

        // 2. ¿Ha caducado?
        if (time() > $pendiente['expira']) {
            Sesion::eliminar('2fa');
            Respuesta::json(["error" => "El código ha caducado. Vuelve a iniciar sesión."], 401);
            return;
        }

        // 3. ¿El código es correcto?
        if (!password_verify($codigo, $pendiente['hash'])) {
            $pendiente['intentos']++;

            if ($pendiente['intentos'] >= 5) {
                Sesion::eliminar('2fa');
                Respuesta::json(["error" => "Demasiados intentos. Vuelve a iniciar sesión."], 429);
                return;
            }

            Sesion::escribir('2fa', $pendiente);
            Respuesta::json(["error" => "Código incorrecto."], 401);
            return;
        }

        // 4. Código correcto: ahora sí, login de verdad
        $usuarioDAO = new UsuarioDAOImplements();
        $usuario = $usuarioDAO->buscarPorId($pendiente['id']);

        if ($usuario === null) {
            Sesion::eliminar('2fa');
            Respuesta::json(["error" => "El usuario ya no existe."], 401);
            return;
        }

        Sesion::eliminar('2fa');
        (new Autenticacion($usuarioDAO))->iniciarSesion($usuario);

        Respuesta::json(["ok" => true]);
    }
}
