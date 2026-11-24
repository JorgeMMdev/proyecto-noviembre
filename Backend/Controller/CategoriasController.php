<?php

namespace App\Controller;

use App\DAO\CategoriaDAOImplements;
use App\Helpers\Acceso;
use App\Helpers\Sesion;
use App\Modelo\Categoria;
use Twig\Environment;

/**
 * Gestión de categorías del panel de admin (PHP y Twig, sin JS).
 *
 *   GET  /categorias            → index()       lista + formulario de alta
 *   POST /categorias            → store()       crear
 *   GET  /categorias/3/editar   → getEditar(3)  formulario de edición
 *   POST /categorias/3/editar   → postEditar(3) guardar cambios
 *   POST /categorias/3/borrar   → postBorrar(3) borrar
 */
class CategoriasController
{
    private CategoriaDAOImplements $categoriaDAO;

    public function __construct(private Environment $twig)
    {
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

        // Con errores se vuelve a pintar la página, con lo que había escrito
        if (!empty($errores)) {
            $this->pintarLista($datos, $errores);
            return;
        }

        $this->categoriaDAO->insertar(new Categoria(null, $datos['nombre'], $datos['descripcion']));

        Sesion::flash("Categoría «{$datos['nombre']}» creada.");
        header('Location: /categorias');
        exit;
    }

    public function getEditar(int $id): void
    {
        Acceso::exigirAdmin();

        $categoria = $this->buscarOFallar($id);

        $this->pintarEdicion($categoria, [
            'nombre'      => $categoria->getNombreCategoria(),
            'descripcion' => $categoria->getDescripcion(),
        ]);
    }

    public function postEditar(int $id): void
    {
        Acceso::exigirAdmin();

        $categoria = $this->buscarOFallar($id);
        [$datos, $errores] = $this->leerFormulario();

        if (!empty($errores)) {
            $this->pintarEdicion($categoria, $datos, $errores);
            return;
        }

        $categoria->setNombreCategoria($datos['nombre']);
        $categoria->setDescripcion($datos['descripcion']);
        $this->categoriaDAO->actualizar($categoria);

        Sesion::flash("Categoría «{$datos['nombre']}» guardada.");
        header('Location: /categorias');
        exit;
    }

    public function postBorrar(int $id): void
    {
        Acceso::exigirAdmin();

        $categoria = $this->buscarOFallar($id);

        // En la BD el borrado va en cascada: se llevaría sus servicios, sus ofertas
        // y el historial de solicitudes. Por eso no se deja si tiene servicios.
        $servicios = $this->categoriaDAO->contarServicios($id);
        if ($servicios > 0) {
            Sesion::flash("No se puede borrar «{$categoria->getNombreCategoria()}»: tiene $servicios servicio(s).", 'error');
        } else {
            $this->categoriaDAO->borrar($id);
            Sesion::flash("Categoría «{$categoria->getNombreCategoria()}» borrada.");
        }

        header('Location: /categorias');
        exit;
    }

    // ---------- Ayudantes ----------

    /** Lee y valida el formulario. Devuelve [datos, errores]. */
    private function leerFormulario(): array
    {
        $datos = [
            'nombre'      => trim((string) filter_input(INPUT_POST, 'nombre')),
            'descripcion' => trim((string) filter_input(INPUT_POST, 'descripcion')),
        ];

        $errores = [];
        if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 100) {
            $errores['nombre'] = 'El nombre es obligatorio y no puede pasar de 100 caracteres.';
        }
        if (mb_strlen($datos['descripcion']) > 1000) {
            $errores['descripcion'] = 'La descripción no puede pasar de 1000 caracteres.';
        }

        if ($datos['descripcion'] === '') {
            $datos['descripcion'] = null;
        }

        return [$datos, $errores];
    }

    private function buscarOFallar(int $id): Categoria
    {
        $categoria = $this->categoriaDAO->buscarPorId($id);

        if ($categoria === null) {
            http_response_code(404);
            echo $this->twig->render('errores/404.html.twig', ['codigo' => 404, 'mensaje' => 'La categoría no existe.']);
            exit;
        }

        return $categoria;
    }

    private function pintarLista(array $datos = [], array $errores = []): void
    {
        // Cada categoría con su número de servicios, para la tabla
        $filas = [];
        foreach ($this->categoriaDAO->listar() as $c) {
            $filas[] = [
                'categoria' => $c,
                'servicios' => $this->categoriaDAO->contarServicios($c->getIdCategoria()),
            ];
        }

        echo $this->twig->render('admin/categorias.html.twig', [
            'filas'   => $filas,
            'datos'   => $datos,
            'errores' => $errores,
            'flash'   => Sesion::tomarFlash(),
        ]);
    }

    private function pintarEdicion(Categoria $categoria, array $datos, array $errores = []): void
    {
        echo $this->twig->render('admin/categoria_editar.html.twig', [
            'categoria' => $categoria,
            'datos'     => $datos,
            'errores'   => $errores,
        ]);
    }
}