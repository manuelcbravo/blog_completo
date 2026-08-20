<?php

namespace Database\Seeders;

use App\Support\Redaccion\ImportadorDeBorradores;
use Illuminate\Database\Seeder;

/**
 * Siembra el contenido real del blog leyendo redaccion/borradores. No inventa
 * publicaciones: si un artículo no tiene archivo, no existe.
 *
 * Es idempotente —vuelve a correrlo y actualiza lo que cambió— y borra a fondo
 * cualquier publicación que ya no tenga borrador detrás, junto con sus vistas,
 * comentarios y taxonomías huérfanas. Así salió el contenido de demostración de
 * las bases que ya lo tenían sembrado.
 *
 * Siembra en silencio, como cualquier seeder. Si quieres ver qué se creó y qué
 * se borró, usa el comando, que hace exactamente lo mismo pero informando:
 *
 *     php artisan blog:redaccion --limpiar
 */
class BlogContenidoSeeder extends Seeder
{
    public function run(): void
    {
        $importador = ImportadorDeBorradores::porDefecto();

        $importador->importar();
        $importador->limpiarLoQueNoEsBorrador();
    }
}
