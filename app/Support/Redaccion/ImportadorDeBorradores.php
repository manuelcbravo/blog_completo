<?php

namespace App\Support\Redaccion;

use App\Enums\EstadoPublicacion;
use App\Enums\TipoPublicacion;
use App\Models\Categoria;
use App\Models\Comentario;
use App\Models\Etiqueta;
use App\Models\Publicacion;
use App\Models\User;
use App\Models\Vista;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Lleva los archivos de redaccion/borradores a la base de datos. Lo usan por
 * igual el comando `blog:redaccion` y el seeder de contenido, para que sembrar
 * y sincronizar no puedan divergir.
 */
class ImportadorDeBorradores
{
    public function __construct(
        private readonly string $directorio,
    ) {}

    public static function porDefecto(): self
    {
        return new self(base_path('redaccion/borradores'));
    }

    /**
     * @return list<Borrador>
     */
    public function borradores(): array
    {
        if (! is_dir($this->directorio)) {
            throw new RuntimeException("No existe el directorio de borradores: {$this->directorio}");
        }

        $archivos = glob($this->directorio.'/*.md') ?: [];
        sort($archivos);

        return array_map(Borrador::desdeArchivo(...), $archivos);
    }

    /**
     * @return array{creadas: int, actualizadas: int, titulos: list<string>}
     */
    public function importar(?User $autor = null): array
    {
        $autor ??= User::query()->orderBy('id')->firstOrFail();

        $creadas = 0;
        $actualizadas = 0;
        $titulos = [];

        foreach ($this->borradores() as $borrador) {
            $modelo = $borrador->tipo()->modelo();

            /** @var Publicacion|null $existente */
            $existente = $modelo::query()->withTrashed()->where('slug', $borrador->slug())->first();

            $publicacion = $existente ?? $borrador->tipo()->nuevoModelo();
            $publicacion->slug = $borrador->slug();

            $publicacion->fill([
                'titulo' => $borrador->titulo(),
                'resumen' => $borrador->resumen(),
                'contenido' => $borrador->contenidoHtml(),
                'estado' => $borrador->estado(),
                'fecha_publicacion' => $borrador->estado() === EstadoPublicacion::Publicado
                    ? now()->subDays($borrador->haceDias())->setTime(9, 0)
                    : null,
                'tiempo_lectura' => $borrador->tiempoLectura(),
                'importante' => $borrador->importante(),
                'tags_seo' => $borrador->tagsSeo(),
                'meta_titulo' => $borrador->metaTitulo(),
                'meta_descripcion' => Str::limit($borrador->metaDescripcion(), 480, ''),
                'id_categoria' => $this->categoria($borrador->categoria())->id,
                'id_autor' => $autor->id,
            ]);

            $publicacion->deleted_at = null;

            if ($existente === null) {
                $creadas++;
            } else {
                $actualizadas++;
            }

            $publicacion->save();
            $publicacion->etiquetas()->sync($this->etiquetas($borrador->etiquetas()));

            $titulos[] = $borrador->titulo();
        }

        return ['creadas' => $creadas, 'actualizadas' => $actualizadas, 'titulos' => $titulos];
    }

    /**
     * Los títulos de lo que `limpiarLoQueNoEsBorrador()` se llevaría, sin
     * borrar nada. Existe para poder enseñar la lista y preguntar antes.
     *
     * @return list<string>
     */
    public function publicacionesSinBorrador(): array
    {
        $slugs = array_map(
            static fn (Borrador $borrador): string => $borrador->slug(),
            $this->borradores(),
        );

        $titulos = [];

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();

            $titulos = array_merge($titulos, $modelo::query()
                ->withTrashed()
                ->whereNotIn('slug', $slugs)
                ->pluck('titulo')
                ->all());
        }

        return array_values(array_map(strval(...), $titulos));
    }

    /**
     * Borra a fondo todo lo que no venga de un borrador: es la vía para sacar
     * el contenido de demostración de una base que ya lo tiene sembrado. Se
     * fuerza el borrado porque las publicaciones usan SoftDeletes y un archivo
     * eliminado no debe seguir ocupando su slug.
     *
     * @return list<string>
     */
    public function limpiarLoQueNoEsBorrador(): array
    {
        $slugs = array_map(
            static fn (Borrador $borrador): string => $borrador->slug(),
            $this->borradores(),
        );

        $eliminadas = [];

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();

            $sobrantes = $modelo::query()
                ->withTrashed()
                ->whereNotIn('slug', $slugs)
                ->get();

            foreach ($sobrantes as $publicacion) {
                $eliminadas[] = $publicacion->titulo;
                $publicacion->etiquetas()->detach();
                $publicacion->forceDelete();
            }

            // Vistas y comentarios sin publicación detrás: sobran siempre, los
            // haya dejado esta corrida o una anterior. Dejarlos infla el
            // tablero con lecturas de artículos que ya no existen.
            $vivos = $modelo::query()->withTrashed()->select('id');

            Vista::query()
                ->where('tipo', $tipo->value)
                ->whereNotNull('post_id')
                ->whereNotIn('post_id', $vivos)
                ->delete();

            Comentario::query()
                ->where('tipo', $tipo->value)
                ->whereNotIn('post_id', $vivos)
                ->delete();
        }

        // Categorías y etiquetas que se quedaron sin nada que clasificar.
        foreach (Categoria::query()->get() as $categoria) {
            if ($this->publicacionesDe($categoria->id) === 0) {
                $eliminadas[] = "Categoría: {$categoria->nombre}";
                $categoria->delete();
            }
        }

        // La etiqueta se relaciona con los tres tipos por la misma pivote, que
        // distingue con su columna `tipo`, así que se cuenta ahí directamente.
        foreach (Etiqueta::query()->get() as $etiqueta) {
            $usos = DB::table('post_tags')->where('tag_id', $etiqueta->id)->count();

            if ($usos === 0) {
                $eliminadas[] = "Etiqueta: {$etiqueta->nombre}";
                $etiqueta->delete();
            }
        }

        return $eliminadas;
    }

    private function publicacionesDe(int $categoriaId): int
    {
        $total = 0;

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();
            $total += $modelo::query()->where('id_categoria', $categoriaId)->count();
        }

        return $total;
    }

    private function categoria(string $nombre): Categoria
    {
        return Categoria::query()->firstOrCreate(
            ['slug' => Str::slug($nombre)],
            ['nombre' => $nombre, 'descripcion' => "Publicaciones sobre {$nombre}."],
        );
    }

    /**
     * @param  list<string>  $nombres
     * @return list<int>
     */
    private function etiquetas(array $nombres): array
    {
        return array_map(
            fn (string $nombre): int => Etiqueta::query()->firstOrCreate(
                ['slug' => Str::slug($nombre)],
                ['nombre' => $nombre],
            )->id,
            $nombres,
        );
    }
}
