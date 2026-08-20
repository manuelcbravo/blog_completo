<?php

namespace App\Console\Commands;

use App\Enums\TipoPublicacion;
use App\Models\Publicacion;
use App\Support\Redaccion\Borrador;
use App\Support\Redaccion\ImportadorDeBorradores;
use Illuminate\Console\Command;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * El camino de vuelta: de la base a `redaccion/borradores`.
 *
 * Sirve para rescatar lo que se escribió directamente en el panel, que sin
 * archivo detrás es justo lo que `blog:redaccion --limpiar` se llevaría.
 */
class ExportarBorradores extends Command
{
    protected $signature = 'blog:exportar
                            {--todas : Exporta todas las publicaciones, no sólo las que no tienen borrador}
                            {--force : Sobrescribe un archivo que ya exista}';

    protected $description = 'Baja publicaciones de la base a redaccion/borradores como Markdown';

    public function handle(): int
    {
        $directorio = base_path('redaccion/borradores');
        $existentes = $this->slugsConBorrador();
        $convertidor = $this->convertidor();

        $escritos = 0;
        $omitidos = 0;

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();

            /** @var iterable<int, Publicacion> $publicaciones */
            $publicaciones = $modelo::query()->with(['categoria', 'etiquetas'])->orderBy('id')->cursor();

            foreach ($publicaciones as $publicacion) {
                if (! $this->option('todas') && in_array($publicacion->slug, $existentes, true)) {
                    continue;
                }

                $ruta = $directorio.'/'.$publicacion->slug.'.md';

                if (file_exists($ruta) && ! $this->option('force')) {
                    $this->components->twoColumnDetail($publicacion->slug, '<fg=yellow>ya existe</>');
                    $omitidos++;

                    continue;
                }

                file_put_contents($ruta, $this->componer($publicacion, $tipo, $convertidor));

                $this->components->twoColumnDetail($publicacion->titulo, '<fg=green>exportada</>');
                $escritos++;
            }
        }

        $this->components->info("{$escritos} exportadas, {$omitidos} omitidas.");

        if ($escritos > 0) {
            $this->components->warn('Revisa el Markdown antes de confiar en él: la conversión desde HTML no es perfecta.');
        }

        return self::SUCCESS;
    }

    private function convertidor(): HtmlConverter
    {
        return new HtmlConverter([
            'header_style' => 'atx',      // ## y no subrayado
            'strip_tags' => true,
            'hard_break' => false,
            'italic_style' => '*',
            'bold_style' => '**',
        ]);
    }

    /**
     * @return list<string>
     */
    private function slugsConBorrador(): array
    {
        return array_map(
            static fn (Borrador $borrador): string => $borrador->slug(),
            ImportadorDeBorradores::porDefecto()->borradores(),
        );
    }

    private function componer(Publicacion $publicacion, TipoPublicacion $tipo, HtmlConverter $convertidor): string
    {
        $frente = array_filter([
            'titulo' => $publicacion->titulo,
            'slug' => $publicacion->slug,
            'tipo' => $tipo->value,
            'estado' => $publicacion->estado->value,
            'categoria' => $publicacion->categoria?->nombre,
            'etiquetas' => $publicacion->etiquetas->pluck('nombre')->all(),
            'resumen' => $publicacion->resumen,
            'meta_titulo' => $publicacion->meta_titulo,
            'meta_descripcion' => $publicacion->meta_descripcion,
            'tags_seo' => $publicacion->tags_seo,
            // La fecha se guarda como antigüedad relativa, que es lo que espera
            // el importador para que el archivo no envejezca solo.
            'hace_dias' => $publicacion->fecha_publicacion === null
                ? null
                : (int) $publicacion->fecha_publicacion->diffInDays(now()),
            'tiempo_lectura' => $publicacion->tiempo_lectura,
            'importante' => $publicacion->importante ?: null,
        ], static fn (mixed $valor): bool => $valor !== null && $valor !== '' && $valor !== []);

        $encabezado = Yaml::dump($frente, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $cuerpo = trim($convertidor->convert((string) $publicacion->contenido));

        return "---\n".$encabezado."---\n\n".$cuerpo."\n";
    }
}
