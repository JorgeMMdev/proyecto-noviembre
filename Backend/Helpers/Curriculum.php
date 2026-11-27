<?php

namespace App\Helpers;

/**
 * Currículums en PDF.
 *
 * Llegan desde el registro en el mismo FormData que el resto de campos
 * ($_FILES['cv']) y se guardan en storage/cvs/usuario_{id}.pdf. Esa carpeta
 * está fuera de public/, así que no se pueden abrir con una URL: solo desde
 * el panel de admin.
 */
class Curriculum
{
    public const TAMANO_MAXIMO = 2 * 1024 * 1024; // 2 MB

    private static function carpeta(): string
    {
        return dirname(__DIR__) . '/storage/cvs';
    }

    public static function ruta(int $idUsuario): string
    {
        return self::carpeta() . "/usuario_$idUsuario.pdf";
    }

    public static function existe(int $idUsuario): bool
    {
        return is_file(self::ruta($idUsuario));
    }

    /** Recibe $_FILES['cv'] y devuelve el mensaje de error, o null si es un PDF válido. */
    public static function validar(?array $fichero): ?string
    {
        if ($fichero === null || $fichero['error'] === UPLOAD_ERR_NO_FILE) {
            return 'Tienes que adjuntar tu currículum en PDF.';
        }
        if ($fichero['error'] === UPLOAD_ERR_INI_SIZE || $fichero['size'] > self::TAMANO_MAXIMO) {
            return 'El currículum no puede pasar de 2 MB.';
        }
        if ($fichero['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($fichero['tmp_name'])) {
            return 'No se pudo subir el currículum.';
        }

        // No vale con mirar la extensión: comprobamos que el contenido es un PDF
        $tipo = (new \finfo(FILEINFO_MIME_TYPE))->file($fichero['tmp_name']);
        $inicio = file_get_contents($fichero['tmp_name'], false, null, 0, 5);
        if ($tipo !== 'application/pdf' || $inicio !== '%PDF-') {
            return 'El currículum tiene que ser un PDF.';
        }

        return null;
    }

    /** Mueve el PDF (ya validado) desde la carpeta temporal de PHP a la nuestra. */
    public static function guardar(array $fichero, int $idUsuario): bool
    {
        if (!is_dir(self::carpeta()) && !mkdir(self::carpeta(), 0775, true)) {
            return false;
        }

        return move_uploaded_file($fichero['tmp_name'], self::ruta($idUsuario));
    }
}
