<?php

namespace App\Console\Commands;

use App\Support\Redaccion\ImportadorDeBorradores;
use Illuminate\Console\Command;
use Throwable;

class SincronizarRedaccion extends Command
{
    protected $signature = 'blog:redaccion
                            {--revisar : Sólo valida los borradores, sin escribir en la base de datos}
                            {--limpiar : Borra a fondo las publicaciones que ya no tienen borrador}
                            {--force : Con --limpiar, borra sin preguntar}';

    protected $description = 'Lleva los borradores de redaccion/borradores al blog';

    public function handle(): int
    {
        $importador = ImportadorDeBorradores::porDefecto();

        try {
            $borradores = $importador->borradores();
        } catch (Throwable $error) {
            $this->components->error($error->getMessage());

            return self::FAILURE;
        }

        if ($borradores === []) {
            $this->components->warn('No hay borradores en redaccion/borradores.');

            return self::SUCCESS;
        }

        foreach ($borradores as $borrador) {
            $this->components->twoColumnDetail(
                $borrador->titulo(),
                sprintf(
                    '%s · %s · %d min',
                    $borrador->tipo()->etiqueta(),
                    $borrador->estado()->label(),
                    $borrador->tiempoLectura(),
                ),
            );
        }

        if ($this->option('revisar')) {
            $this->components->info(count($borradores).' borradores válidos. No se escribió nada.');

            return self::SUCCESS;
        }

        $resultado = $importador->importar();

        if ($this->option('limpiar')) {
            $this->limpiar($importador);
        }

        $this->components->info(sprintf(
            '%d publicaciones nuevas, %d actualizadas.',
            $resultado['creadas'],
            $resultado['actualizadas'],
        ));

        return self::SUCCESS;
    }

    /**
     * Borra lo que ya no tiene borrador, preguntando primero.
     *
     * La confirmación no es ceremonia: si alguien escribió una publicación
     * directamente en el panel, aquí no hay archivo que la respalde y el
     * borrado se la lleva. Enseñar la lista antes es la diferencia entre una
     * limpieza y una pérdida.
     */
    private function limpiar(ImportadorDeBorradores $importador): void
    {
        $sobrantes = $importador->publicacionesSinBorrador();

        if ($sobrantes === []) {
            return;
        }

        $this->newLine();
        $this->components->warn('Sin borrador que las respalde, y por lo tanto a punto de borrarse:');

        foreach ($sobrantes as $titulo) {
            $this->components->twoColumnDetail($titulo, '<fg=red>se eliminará</>');
        }

        if (! $this->option('force') && ! $this->confirm('¿Borrar estas '.count($sobrantes).' publicaciones a fondo?', false)) {
            $this->components->info('No se borró nada. Escríbelas como borrador en redaccion/borradores para conservarlas.');

            return;
        }

        $importador->limpiarLoQueNoEsBorrador();

        $this->components->info(count($sobrantes).' publicaciones eliminadas.');
    }
}
