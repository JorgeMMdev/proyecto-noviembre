<?php

namespace App\Controller;

use App\DAO\BD;
use App\DAO\OfertaProfesionalDAOImplements;
use App\DAO\SolicitudDAOImplements;
use App\DAO\UsuarioDAOImplements;
use App\Helpers\Acceso;
use App\Helpers\Sesion;
use App\Modelo\EstadoSolicitud;
use App\Modelo\Rol;
use App\Modelo\Usuario;
use Twig\Environment;

/**
 * Usuarios del panel de admin (PHP y Twig).
 *
 *   GET  /usuarios                → index()           lista
 *   POST /usuarios/5/baja         → postBaja(5)       desactivar
 *   POST /usuarios/5/reactivar    → postReactivar(5)  volver a activar
 *
 * "Dar de baja" no borra: desactiva. Así se conserva el historial de puntos
 * y de solicitudes, que también es de otros usuarios.
 */
class UsuariosController
{
    private UsuarioDAOImplements $usuarioDAO;

    public function __construct(private Environment $twig)
    {
        $this->usuarioDAO = new UsuarioDAOImplements();
    }

    public function index(): void
    {
        Acceso::exigirAdmin();

        echo $this->twig->render('admin/usuarios.html.twig', [
            'usuarios' => $this->usuarioDAO->listarParaAdmin(),
            'flash'    => Sesion::tomarFlash(),
        ]);
    }

    public function postBaja(int $id): void
    {
        Acceso::exigirAdmin();

        $usuario = $this->buscarOFallar($id);

        if ($usuario->getRol() === Rol::ADMIN) {
            $this->volver("No se puede dar de baja a un administrador.", 'error');
        }
        if (!$usuario->isActivo()) {
            $this->volver("{$usuario->getNombre()} ya estaba dado de baja.", 'error');
        }

        $solicitudDAO = new SolicitudDAOImplements();
        $pdo = BD::conectar();
        $canceladas = 0;

        try {
            $pdo->beginTransaction();

            // 1. El usuario ya no puede entrar
            $this->usuarioDAO->cambiarActivo($id, false);

            // 2. Sus ofertas dejan de verse en el buscador
            (new OfertaProfesionalDAOImplements())->desactivarTodasDe($id);

            // 3. Sus solicitudes abiertas (como cliente o como profesional) se cancelan
            //    y cada cliente recupera los puntos que tenía en movimiento
            foreach ($solicitudDAO->listarAbiertasDe($id) as $s) {
                $cambiada = $solicitudDAO->cambiarEstado(
                    (int) $s['id_solicitud'],
                    EstadoSolicitud::from($s['estado']),
                    EstadoSolicitud::CANCELADO
                );
                if ($cambiada) {
                    $this->usuarioDAO->sumarSaldo((int) $s['id_solicitante'], (int) $s['precio_final']);
                    $canceladas++;
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->volver("No se pudo dar de baja al usuario.", 'error');
        }

        $mensaje = "{$usuario->getNombre()} {$usuario->getApellidos()} ha sido dado de baja.";
        if ($canceladas > 0) {
            $mensaje .= " Se han cancelado $canceladas solicitud(es) y devuelto los puntos.";
        }
        $this->volver($mensaje);
    }

    public function postReactivar(int $id): void
    {
        Acceso::exigirAdmin();

        $usuario = $this->buscarOFallar($id);
        $this->usuarioDAO->cambiarActivo($id, true);

        // Sus ofertas siguen desactivadas: las vuelve a activar él desde su perfil
        $this->volver("{$usuario->getNombre()} {$usuario->getApellidos()} vuelve a estar activo. Sus ofertas siguen desactivadas hasta que las active desde su perfil.");
    }

    // ---------- Ayudantes ----------

    private function buscarOFallar(int $id): Usuario
    {
        $usuario = $this->usuarioDAO->buscarPorId($id);

        if ($usuario === null) {
            http_response_code(404);
            echo $this->twig->render('errores/404.html.twig', ['codigo' => 404, 'mensaje' => 'El usuario no existe.']);
            exit;
        }

        return $usuario;
    }

    /** Guarda el mensaje y vuelve a la lista. */
    private function volver(string $mensaje, string $tipo = 'exito'): never
    {
        Sesion::flash($mensaje, $tipo);
        header('Location: /usuarios');
        exit;
    }
}