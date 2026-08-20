<?php

namespace App\Console\Commands;

use App\Enums\EstadoPublicacion;
use App\Enums\TipoPublicacion;
use Illuminate\Console\Command;

class PublicarProgramadas extends Command
{
    /**
     * @var string
     */
    protected $signature = 'blog:publicar-programadas {--pendientes : Solo muestra lo que esta en cola, sin publicar}';

    /**
     * @var string
     */
    protected $description = 'Publica las publicaciones programadas cuya fecha ya llego';

    public function handle(): int
    {
        $soloVer = (bool) $this->option('pendientes');
        $publicadas = 0;
        $enEspera = 0;
        $atoradas = 0;

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();

            $programadas = $modelo::query()
                ->where('estado', EstadoPublicacion::Programado->value)
                ->orderBy('fecha_publicacion')
                ->get();

            foreach ($programadas as $publicacion) {
                if ($publicacion->fecha_publicacion === null) {
                    $atoradas++;
                    $this->components->warn("Sin fecha, nunca saldra: {$publicacion->titulo}");

                    continue;
                }

                if ($publicacion->fecha_publicacion->isFuture()) {
                    $enEspera++;
                    $this->components->info(
                        $publicacion->fecha_publicacion->format('d/m/Y H:i')." · {$publicacion->titulo}"
                    );

                    continue;
                }

                if ($soloVer) {
                    $this->components->info("Lista para publicarse: {$publicacion->titulo}");

                    continue;
                }

                $publicacion->estado = EstadoPublicacion::Publicado;
                $publicacion->save();
                $publicadas++;

                $this->components->info("Publicada: {$publicacion->titulo}");
            }
        }

        $this->components->info(sprintf(
            '%d publicadas, %d en espera%s.',
            $publicadas,
            $enEspera,
            $atoradas > 0 ? ", {$atoradas} sin fecha" : '',
        ));

        return self::SUCCESS;
    }
}
