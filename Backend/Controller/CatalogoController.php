<?php

namespace App\Controller;

use App\DAO\CategoriaDAOImplements;
use App\DAO\ServicioDAOImplements;
use App\Helpers\Acceso;
use App\Helpers\Sesion;
use App\Helpers\Tabla;
use App\Modelo\Servicio;
use Twig\Environment;

/**
 * Catálogo oficial de servicios del panel de admin (PHP y Twig, sin JS).
 * Se llama /catalogo para no chocar con /servicios, que es el buscador público.
 *
 *   GET  /catalogo            → index()       lista + formulario de alta (?q=clases&orden=referencia&dir=desc)
 *   POST /catalogo            → store()       crear
 *   GET  /catalogo/4/editar   → getEditar(4)  formulario de edición
 *   POST /catalogo/4/editar   → postEditar(4) guardar cambios
 *   POST /catalogo/4/borrar   → postBorrar(4) borrar
 */
class CatalogoController
{
    private ServicioDAOImplements $servicioDAO;
    private CategoriaDAOImplements $categoriaDAO;

    public function __construct(private Environment $twig)
    {
        $this->servicioDAO = new ServicioDAOImplements();
        $this->categoriaDAO = new CategoriaDAOImplements();
    }

    public function index(): void
    {
        Acceso::exigirAdmin();
        $this->pintarLista();
    }

    public function store(): void
    {
        Acceso::exigirAdmin();

        [$datos, $errores] = $this->leerFormulario();

        if (!empty($errores)) {
            $this->pintarLista($datos, $errores);
            return;
        }

        $this->servicioDAO->insertar(new Servicio(
            null,
            $datos['idCategoria'],
            $datos['nombre'],
            $datos['precio'],
            $datos['tiempo'],
            $datos['descripcion']
        ));

        Sesion::flash("Servicio «{$datos['nombre']}» creado.");
        header('Location: /catalogo');
        exit;
    }

    public function getEditar(int $id): void
    {
        Acceso::exigirAdmin();

        $servicio = $this->buscarOFallar($id);

        $this->pintarEdicion($servicio, [
            'nombre'      => $servicio->getNombreServicio(),
            'idCategoria' => $servicio->getIdCategoria(),
            'precio'      => $servicio->getPrecioMedioEstimado(),
            'tiempo'      => $servicio->getTiempoMedioEstimado(),
            'descripcion' => $servicio->getDescripcion(),
        ]);
    }

    public function postEditar(int $id): void
    {
        Acceso::exigirAdmin();

        $servicio = $this->buscarOFallar($id);
        [$datos, $errores] = $this->leerFormulario();

        if (!empty($errores)) {
            $this->pintarEdicion($servicio, $datos, $errores);
            return;
        }

        $servicio->setIdCategoria($datos['idCategoria']);
        $servicio->setNombreServicio($datos['nombre']);
        $servicio->setPrecioMedioEstimado($datos['precio']);
        $servicio->setTiempoMedioEstimado($datos['tiempo']);
        $servicio->setDescripcion($datos['descripcion']);
        $this->servicioDAO->actualizar($servicio);

        Sesion::flash("Servicio «{$datos['nombre']}» guardado.");
        header('Location: /catalogo');
        exit;
    }

    public function postBorrar(int $id): void
    {
        Acceso::exigirAdmin();

        $servicio = $this->buscarOFallar($id);

        // Borrar en cascada se llevaría las ofertas y el historial de solicitudes
        $ofertas = $this->servicioDAO->contarOfertas($id);
        if ($ofertas > 0) {
            Sesion::flash("No se puede borrar «{$servicio->getNombreServicio()}»: lo ofrecen $ofertas profesional(es).", 'error');
        } else {
            $this->servicioDAO->borrar($id);
            Sesion::flash("Servicio «{$servicio->getNombreServicio()}» borrado.");
        }

        header('Location: /catalogo');
        exit;
    }

    // ---------- Ayudantes ----------

    /** Lee y valida el formulario. Devuelve [datos, errores]. */
    private function leerFormulario(): array
    {
        $datos = [
            'nombre'      => trim((string) filter_input(INPUT_POST, 'nombre')),
            'idCategoria' => filter_input(INPUT_POST, 'idCategoria', FILTER_VALIDATE_INT),
            'precio'      => filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_INT),
            'tiempo'      => filter_input(INPUT_POST, 'tiempo', FILTER_VALIDATE_FLOAT),
            'descripcion' => trim((string) filter_input(INPUT_POST, 'descripcion')),
        ];

        $errores = [];

        if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 150) {
            $errores['nombre'] = 'El nombre es obligatorio y no puede pasar de 150 caracteres.';
        }
        if (!is_int($datos['idCategoria']) || $this->categoriaDAO->buscarPorId($datos['idCategoria']) === null) {
            $errores['idCategoria'] = 'Elige una categoría.';
        }
        if (!is_int($datos['precio']) || $datos['precio'] < 1 || $datos['precio'] > 100) {
            $errores['precio'] = 'El precio de referencia debe estar entre 1 y 100 pts/h.';
        }
        if (!is_float($datos['tiempo']) || $datos['tiempo'] < 0.5 || $datos['tiempo'] > 24 || fmod($datos['tiempo'], 0.5) != 0) {
            $errores['tiempo'] = 'El tiempo estimado debe ir de 0,5 a 24 h, en tramos de media hora.';
        }
        if (mb_strlen($datos['descripcion']) > 1000) {
            $errores['descripcion'] = 'La descripción no puede pasar de 1000 caracteres.';
        }

        if ($datos['descripcion'] === '') {
            $datos['descripcion'] = null;
        }

        return [$datos, $errores];
    }

    private function buscarOFallar(int $id): Servicio
    {
        $servicio = $this->servicioDAO->buscarPorId($id);

        if ($servicio === null) {
            http_response_code(404);
            echo $this->twig->render('errores/404.html.twig', ['codigo' => 404, 'mensaje' => 'El servicio no existe.']);
            exit;
        }

        return $servicio;
    }

    private function pintarLista(array $datos = [], array $errores = []): void
    {
        $categorias = $this->categoriaDAO->listar();

        // Nombre de cada categoría por su id
        $nombres = [];
        foreach ($categorias as $c) {
            $nombres[$c->getIdCategoria()] = $c->getNombreCategoria();
        }

        // Cada servicio con su categoría y cuántos profesionales lo ofrecen
        $filas = [];
        foreach ($this->servicioDAO->listar() as $s) {
            $filas[] = [
                'servicio'  => $s,
                'categoria' => $nombres[$s->getIdCategoria()] ?? '—',
                'ofertas'   => $this->servicioDAO->contarOfertas($s->getIdServicio()),
            ];
        }

        // Buscador: por nombre del servicio o de su categoría
        $buscado = Tabla::leerBusqueda();
        $filas = Tabla::filtrar($filas, $buscado, fn($f) => [$f['servicio']->getNombreServicio(), $f['categoria']]);

        $columnas = [
            'servicio'      => fn($f) => $f['servicio']->getNombreServicio(),
            'categoria'     => fn($f) => $f['categoria'],
            'referencia'    => fn($f) => $f['servicio']->getPrecioMedioEstimado(),
            'tiempo'        => fn($f) => (float) $f['servicio']->getTiempoMedioEstimado(),
            'profesionales' => fn($f) => $f['ofertas'],
        ];
        $orden = Tabla::leerOrden($columnas, 'servicio');
        $filas = Tabla::ordenar($filas, $columnas, $orden['orden'], $orden['dir']);

        echo $this->twig->render('admin/catalogo.html.twig', [
            'filas'      => $filas,
            'buscado'    => $buscado,
            'tabla'      => Tabla::paraVista($orden, ['q' => $buscado]),
            'categorias' => $categorias,
            'datos'      => $datos,
            'errores'    => $errores,
            'flash'      => Sesion::tomarFlash(),
        ]);
    }

    private function pintarEdicion(Servicio $servicio, array $datos, array $errores = []): void
    {
        echo $this->twig->render('admin/catalogo_editar.html.twig', [
            'servicio'   => $servicio,
            'categorias' => $this->categoriaDAO->listar(),
            'datos'      => $datos,
            'errores'    => $errores,
        ]);
    }
}