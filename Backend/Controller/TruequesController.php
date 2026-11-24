<?php

namespace App\Controller;

use App\DAO\BD;
use App\DAO\SolicitudDAOImplements;
use App\DAO\UsuarioDAOImplements;
use App\DAO\ValoracionDAOImplements;
use App\Helpers\Acceso;
use App\Helpers\Sesion;
use App\Modelo\EstadoSolicitud;
use Twig\Environment;

/**
 * Historial global de trueques del panel de admin (PHP y Twig).
 *
 *   GET  /trueques?estado=ACEPTADO  → index()         lista, con filtro opcional por estado
 *   POST /trueques/7/cancelar       → postCancelar(7) cancela y devuelve los puntos al cliente
 *   POST /trueques/7/moderar        → postModerar(7)  elimina la valoración (reseña inapropiada)
 */
class TruequesController
{
    private SolicitudDAOImplements $solicitudDAO;

    public function __construct(private Environment $twig)
    {
        $this->solicitudDAO = new SolicitudDAOImplements();
    }

    public function index(): void
    {
        Acceso::exigirAdmin();

        // Si el estado del filtro no es válido, tryFrom devuelve null y se muestran todos
        $estado = EstadoSolicitud::tryFrom((string) filter_input(INPUT_GET, 'estado'));

        echo $this->twig->render('admin/trueques.html.twig', [
            'trueques' => $this->solicitudDAO->listarTodas($estado),
            'resumen'  => $this->solicitudDAO->resumenGlobal(),
            'estados'  => EstadoSolicitud::cases(),
            'filtro'   => $estado?->value,
            'flash'    => Sesion::tomarFlash(),
        ]);
    }

    public function postCancelar(int $id): void
    {
        Acceso::exigirAdmin();

        $pdo = BD::conectar();
        try {
            $pdo->beginTransaction();

            // Leer bloqueando la fila: nadie puede aceptarla o confirmarla mientras tanto
            $fila = $this->solicitudDAO->buscarPorId($id, true);
            if ($fila === null) {
                $pdo->rollBack();
                $this->volver("La solicitud no existe.", 'error');
            }

            $abiertos = ['SOLICITADO', 'ACEPTADO', 'EN_EJECUCION'];
            if (!in_array($fila['estado'], $abiertos, true)) {
                $pdo->rollBack();
                $this->volver("Solo se pueden cancelar solicitudes sin terminar.", 'error');
            }

            $this->solicitudDAO->cambiarEstado($id, EstadoSolicitud::from($fila['estado']), EstadoSolicitud::CANCELADO);

            // El cliente recupera los puntos que tenía retenidos
            (new UsuarioDAOImplements())->sumarSaldo((int) $fila['id_solicitante'], (int) $fila['precio_final']);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->volver("No se pudo cancelar la solicitud.", 'error');
        }

        $this->volver("Solicitud #$id cancelada. Se han devuelto {$fila['precio_final']} pts al cliente.");
    }

    public function postModerar(int $id): void
    {
        Acceso::exigirAdmin();

        $fila = $this->solicitudDAO->buscarPorId($id);
        if ($fila === null) {
            $this->volver("La solicitud no existe.", 'error');
        }

        $pdo = BD::conectar();
        try {
            $pdo->beginTransaction();

            $borrada = (new ValoracionDAOImplements())->borrarDeSolicitud($id);
            if (!$borrada) {
                $pdo->rollBack();
                $this->volver("Esa solicitud no tiene valoración.", 'error');
            }

            // Sin esa valoración, la media del profesional cambia
            (new UsuarioDAOImplements())->actualizarValoracionMedia((int) $fila['id_profesional']);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->volver("No se pudo eliminar la valoración.", 'error');
        }

        $this->volver("Valoración de la solicitud #$id eliminada y media del profesional recalculada.");
    }

    /** Guarda el mensaje y vuelve a la lista, conservando el filtro que hubiera. */
    private function volver(string $mensaje, string $tipo = 'exito'): never
    {
        Sesion::flash($mensaje, $tipo);
        $estado = (string) filter_input(INPUT_POST, 'filtro');
        header('Location: /trueques' . ($estado !== '' ? '?estado=' . urlencode($estado) : ''));
        exit;
    }
}