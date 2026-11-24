<?php

namespace App\Controller;

use App\DAO\CategoriaDAOImplements;
use App\DAO\OfertaProfesionalDAOImplements;
use App\DAO\ServicioDAOImplements;
use App\DAO\UsuarioDAOImplements;
use App\Helpers\Acceso;
use App\Helpers\Sesion;
use App\Helpers\Tarifa;
use Twig\Environment;

class PerfilController
{
    public function __construct(private Environment $twig) {}

    // GET /perfil
    public function index(): void
    {
        $sesion = Acceso::exigirLogin();

        $usuarioDAO = new UsuarioDAOImplements();
        $perfil = $usuarioDAO->buscarPorId($sesion['id']);
        if ($perfil === null) {
            // La cuenta ya no existe: la sesión no vale
            Sesion::destroy();
            header('Location: /auth/login');
            exit;
        }

        // Mis ofertas, cada una con el margen de tarifa permitido
        $ofertas = [];
        $yaOfrecidos = [];
        foreach ((new OfertaProfesionalDAOImplements())->listarPorUsuario($perfil->getIdUsuario()) as $o) {
            $referencia = (int) $o['precio_medio_estimado'];
            $o['minimo'] = Tarifa::minima($referencia);
            $o['maximo'] = Tarifa::maxima($referencia);
            $ofertas[] = $o;
            $yaOfrecidos[] = (int) $o['id_servicio'];
        }

        // Servicios del catálogo que aún no ofrezco, agrupados por categoría para el desplegable
        $nombresCategoria = [];
        foreach ((new CategoriaDAOImplements())->listar() as $c) {
            $nombresCategoria[$c->getIdCategoria()] = $c->getNombreCategoria();
        }

        $disponibles = [];
        foreach ((new ServicioDAOImplements())->listar() as $s) {
            if (in_array($s->getIdServicio(), $yaOfrecidos, true)) {
                continue;
            }
            $categoria = $nombresCategoria[$s->getIdCategoria()] ?? 'Otros';
            $disponibles[$categoria][] = [
                'id'         => $s->getIdServicio(),
                'nombre'     => $s->getNombreServicio(),
                'referencia' => $s->getPrecioMedioEstimado(),
                'minimo'     => Tarifa::minima($s->getPrecioMedioEstimado()),
                'maximo'     => Tarifa::maxima($s->getPrecioMedioEstimado()),
            ];
        }
        ksort($disponibles);

        echo $this->twig->render('perfil/index.html.twig', [
            'perfil'      => $perfil,
            'stats'       => $usuarioDAO->estadisticas($perfil->getIdUsuario()),
            'ofertas'     => $ofertas,
            'disponibles' => $disponibles,
            'flash'       => Sesion::tomarFlash(),
            // Si un formulario volvió con errores: qué modal abrir, lo que se escribió y los mensajes
            'formulario'  => $this->tomarFormulario(),
        ]);
    }

    private function tomarFormulario(): ?array
    {
        $formulario = Sesion::leer('formOferta');
        Sesion::eliminar('formOferta');
        return $formulario;
    }
}