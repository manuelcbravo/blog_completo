<?php

namespace App\Support\Redaccion;

use App\Enums\EstadoPublicacion;
use App\Enums\TipoPublicacion;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Un borrador del entorno de redacción: un archivo Markdown con encabezado
 * YAML. La fuente de verdad del contenido es el archivo, no la base de datos,
 * para poder escribir en el editor de siempre y versionarlo con git.
 *
 * Ver redaccion/README.md.
 */
class Borrador
{
    /**
     * @param  array<string, mixed>  $frente
     */
    private function __construct(
        public readonly string $archivo,
        public readonly array $frente,
        public readonly string $cuerpo,
    ) {}

    public static function desdeArchivo(string $ruta): self
    {
        $crudo = file_get_contents($ruta);

        if ($crudo === false) {
            throw new RuntimeException("No se pudo leer el borrador: {$ruta}");
        }

        $crudo = str_replace("\r\n", "\n", $crudo);

        if (! str_starts_with($crudo, "---\n")) {
            throw new RuntimeException("El borrador {$ruta} no empieza con el encabezado ---.");
        }

        $partes = explode("\n---\n", substr($crudo, 4), 2);

        if (count($partes) !== 2) {
            throw new RuntimeException("El borrador {$ruta} no cierra el encabezado con ---.");
        }

        $frente = Yaml::parse($partes[0]);

        if (! is_array($frente)) {
            throw new RuntimeException("El encabezado de {$ruta} no es un mapa YAML válido.");
        }

        return new self($ruta, $frente, trim($partes[1]));
    }

    public function slug(): string
    {
        $slug = $this->frente['slug'] ?? null;

        return is_string($slug) && $slug !== ''
            ? $slug
            : Str::slug($this->titulo());
    }

    public function titulo(): string
    {
        return $this->texto('titulo', 'Sin título');
    }

    public function tipo(): TipoPublicacion
    {
        return TipoPublicacion::from($this->texto('tipo', 'post'));
    }

    public function estado(): EstadoPublicacion
    {
        return EstadoPublicacion::from($this->texto('estado', 'borrador'));
    }

    public function categoria(): string
    {
        return $this->texto('categoria', 'General');
    }

    /**
     * @return list<string>
     */
    public function etiquetas(): array
    {
        $valor = $this->frente['etiquetas'] ?? [];

        if (is_string($valor)) {
            $valor = explode(',', $valor);
        }

        if (! is_array($valor)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $etiqueta): string => trim((string) $etiqueta),
            $valor,
        )));
    }

    public function resumen(): string
    {
        return $this->texto('resumen', '');
    }

    public function metaTitulo(): string
    {
        return $this->texto('meta_titulo', $this->titulo());
    }

    public function metaDescripcion(): string
    {
        return $this->texto('meta_descripcion', $this->resumen());
    }

    public function tagsSeo(): string
    {
        return $this->texto('tags_seo', implode(', ', $this->etiquetas()));
    }

    public function importante(): bool
    {
        return (bool) ($this->frente['importante'] ?? false);
    }

    /**
     * Los días que la publicación lleva en línea, contados hacia atrás desde
     * hoy. Se guarda como número relativo y no como fecha fija para que el
     * blog sembrado no envejezca solo cada vez que pasa una semana.
     */
    public function haceDias(): int
    {
        return (int) ($this->frente['hace_dias'] ?? 0);
    }

    /**
     * Estimación estándar de 200 palabras por minuto, mínimo un minuto.
     */
    public function tiempoLectura(): int
    {
        $declarado = (int) ($this->frente['tiempo_lectura'] ?? 0);

        if ($declarado > 0) {
            return $declarado;
        }

        return max(1, (int) ceil(str_word_count(strip_tags($this->cuerpo)) / 200));
    }

    public function contenidoHtml(): string
    {
        return Str::markdown($this->cuerpo, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);
    }

    private function texto(string $clave, string $porDefecto): string
    {
        $valor = $this->frente[$clave] ?? null;

        return is_string($valor) && trim($valor) !== '' ? trim($valor) : $porDefecto;
    }
}
