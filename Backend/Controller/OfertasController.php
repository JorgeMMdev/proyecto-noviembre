<?php

namespace App\Controller;

use App\DAO\OfertaProfesionalDAOImplements;
use App\DAO\ServicioDAOImplements;
use App\Helpers\Acceso;
use App\Helpers\Sesion;
use App\Helpers\Tarifa;
use App\Modelo\OfertaProfesional;
use Twig\Environment;

/**
 * Ofertas del profesional, desde su perfil (PHP y Twig).
 * Los formularios se abren en un modal, pero se envían con un POST normal.
 *
 *   POST /ofertas            → store()        añadir una oferta
 *   POST /ofertas/5/editar   → postEditar(5)  cambiar tarifa, descripción o estado
 *
 * Tras cada acción se vuelve a /perfil. Si hay errores, se guardan en la sesión
 * para que el perfil vuelva a abrir el modal con los mensajes.
 */
class OfertasController
{
    private OfertaProfesionalDAOImplements $ofertaDAO;

    public function __construct(private Environment $twig)
    {
        $this->ofertaDAO = new OfertaProfesionalDAOImplements();
    }

    public function store(): void
    {
        $sesion = Acceso::exigirLogin();

        $idServicio = filter_input(INPUT_POST, 'idServicio', FILTER_VALIDATE_INT);
        $datos = $this->leerCampos();
        $datos['idServicio'] = $idServicio;
        $errores = [];

        $servicio = is_int($idServicio) ? (new ServicioDAOImplements())->buscarPorId($idServicio) : null;
        if ($servicio === null) {
            $errores['idServicio'] = 'Elige un servicio del catálogo.';
        } elseif ($this->ofertaDAO->ofrece($sesion['id'], $idServicio)) {
            $errores['idServicio'] = 'Ya ofreces este servicio.';
        } else {
            $errores += $this->validarCampos($datos, $servicio->getPrecioMedioEstimado());
        }

        if (!empty($errores)) {
            $this->volverConErrores('tplNuevaOferta', $datos, $errores);
        }

        $this->ofertaDAO->insertar(new OfertaProfesional(
            null,
            $sesion['id'],
            $idServicio,
            $datos['puntosHora'],
            $datos['descripcion'],
            true
        ));

        $this->volver("Oferta de «{$servicio->getNombreServicio()}» añadida.");
    }

    public function postEditar(int $id): void
    {
        $sesion = Acceso::exigirLogin();

        // La oferta tiene que existir y ser mía: el id va en la URL y se puede cambiar a mano
        $oferta = $this->ofertaDAO->buscarPorId($id);
        if ($oferta === null || $oferta->getIdUsuario() !== $sesion['id']) {
            $this->volver("No puedes modificar esa oferta.", 'error');
        }

        $servicio = (new ServicioDAOImplements())->buscarPorId($oferta->getIdServicio());
        $datos = $this->leerCampos();
        $datos['activo'] = filter_input(INPUT_POST, 'activo') !== null;   // casilla marcada o no

        $errores = $this->validarCampos($datos, $servicio->getPrecioMedioEstimado());
        if (!empty($errores)) {
            $this->volverConErrores("tplEditarOferta$id", $datos, $errores);
        }

        $oferta->setPuntosHora($datos['puntosHora']);
        $oferta->setDescripcionPersonal($datos['descripcion']);
        $oferta->setActivo($datos['activo']);
        $this->ofertaDAO->actualizar($oferta);

        $this->volver("Oferta de «{$servicio->getNombreServicio()}» guardada.");
    }

    // ---------- Ayudantes ----------

    private function leerCampos(): array
    {
        $descripcion = trim((string) filter_input(INPUT_POST, 'descripcion'));

        return [
            'puntosHora'  => filter_input(INPUT_POST, 'puntosHora', FILTER_VALIDATE_INT),
            'descripcion' => $descripcion === '' ? null : $descripcion,
        ];
    }

    private function validarCampos(array $datos, int $referencia): array
    {
        $errores = [];

        $min = Tarifa::minima($referencia);
        $max = Tarifa::maxima($referencia);
        if (!is_int($datos['puntosHora']) || !Tarifa::permitida($datos['puntosHora'], $referencia)) {
            $errores['puntosHora'] = "La tarifa debe estar entre $min y $max pts/h (referencia: $referencia pts/h).";
        }

        if ($datos['descripcion'] !== null && mb_strlen($datos['descripcion']) > 500) {
            $errores['descripcion'] = 'La descripción no puede pasar de 500 caracteres.';
        }

        return $errores;
    }

    private function volver(string $mensaje, string $tipo = 'exito'): never
    {
        Sesion::flash($mensaje, $tipo);
        header('Location: /perfil');
        exit;
    }

    /** Guarda lo escrito y los errores en la sesión, y vuelve al perfil, que reabrirá el modal. */
    private function volverConErrores(string $modal, array $datos, array $errores): never
    {
        Sesion::escribir('formOferta', ['modal' => $modal, 'datos' => $datos, 'errores' => $errores]);
        header('Location: /perfil');
        exit;
    }
}