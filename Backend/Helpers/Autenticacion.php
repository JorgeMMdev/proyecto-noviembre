<?php

namespace App\Helpers;

use App\DAO\Interfaces\UsuarioDAOInterface;
use App\Modelo\Usuario;
use App\Service\MailService;

class Autenticacion
{
    private const MINUTOS_CODIGO = 5;
    private const MAX_INTENTOS = 5;

    public function __construct(private UsuarioDAOInterface $usuarioDAO) {}

    public function verificarCredenciales(string $correo, string $contrasena): ?Usuario
    {
        $correo = trim(strtolower($correo));

        $usuario = $this->usuarioDAO->buscarPorCorreo($correo);

        if ($usuario === null) {
            return null;
        }

        if (!password_verify($contrasena, $usuario->getContrasena())) {
            return null;
        }

        return $usuario;
    }

    // ---------- Doble factor por correo ----------

    /**
     * Genera un código de 6 cifras, lo guarda (como hash) en la sesión y lo envía por correo.
     * Todavía NO inicia sesión. Devuelve false si el correo no se pudo enviar.
     */
    public function enviarCodigo(Usuario $usuario): bool
    {
        $codigo = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);

        Sesion::escribir('2fa', [
            'id'       => $usuario->getIdUsuario(),
            'correo'   => $usuario->getCorreo(),
            'hash'     => password_hash($codigo, PASSWORD_DEFAULT),
            'expira'   => time() + self::MINUTOS_CODIGO * 60,
            'intentos' => 0,
        ]);

        return MailService::desdeEntorno()->enviarCodigoLogin($usuario, $codigo);
    }

    /** ¿Hay un inicio de sesión esperando el código? */
    public function hayCodigoPendiente(): bool
    {
        return is_array(Sesion::leer('2fa'));
    }

    /** Correo al que se envió el código, para mostrarlo en la página */
    public function correoPendiente(): ?string
    {
        return Sesion::leer('2fa')['correo'] ?? null;
    }

    /**
     * Comprueba el código. Si es correcto, inicia la sesión y devuelve null.
     * Si no, devuelve el mensaje de error. Cuando el intento ya no vale
     * (caducado o demasiados fallos) borra el pendiente y hay que volver a empezar.
     */
    public function verificarCodigo(string $codigo): ?string
    {
        $pendiente = Sesion::leer('2fa');
        if (!is_array($pendiente)) {
            return "No hay ningún inicio de sesión pendiente.";
        }

        if (time() > $pendiente['expira']) {
            Sesion::eliminar('2fa');
            return "El código ha caducado. Vuelve a iniciar sesión.";
        }

        if (!password_verify(trim($codigo), $pendiente['hash'])) {
            $pendiente['intentos']++;

            if ($pendiente['intentos'] >= self::MAX_INTENTOS) {
                Sesion::eliminar('2fa');
                return "Demasiados intentos. Vuelve a iniciar sesión.";
            }

            Sesion::escribir('2fa', $pendiente);
            $quedan = self::MAX_INTENTOS - $pendiente['intentos'];
            return "Código incorrecto. Te quedan $quedan intentos.";
        }

        $usuario = $this->usuarioDAO->buscarPorId($pendiente['id']);
        Sesion::eliminar('2fa');

        if ($usuario === null) {
            return "El usuario ya no existe.";
        }

        $this->iniciarSesion($usuario);
        return null;
    }

    // ---------- Sesión ----------

    public function iniciarSesion(Usuario $usuario): void
    {
        //Seguridad contra ataque de fijacion de sesion.
        Sesion::regenerar();

        Sesion::escribir('usuario', [
            'id' => $usuario->getIdUsuario(),
            'nombre' => $usuario->getNombre(),
            'rol' => $usuario->getRol()->value
        ]);
    }

    public function logout(): void
    {
        Sesion::destroy();
    }
}