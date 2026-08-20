<?php

namespace App\Enums;

use App\Models\Post;
use App\Models\Publicacion;
use App\Models\Recurso;
use App\Models\Tutorial;

enum TipoPublicacion: string
{
    case Post = 'post';
    case Tutorial = 'tutorial';
    case Recurso = 'recurso';

    public static function desdeSegmento(string $segmento): self
    {
        return match ($segmento) {
            'posts' => self::Post,
            'tutoriales' => self::Tutorial,
            'recursos' => self::Recurso,
            default => throw new \ValueError("Tipo de publicación desconocido: {$segmento}"),
        };
    }

    /**
     * @return list<string>
     */
    public static function segmentos(): array
    {
        return ['posts', 'tutoriales', 'recursos'];
    }

    public function segmento(): string
    {
        return match ($this) {
            self::Post => 'posts',
            self::Tutorial => 'tutoriales',
            self::Recurso => 'recursos',
        };
    }

    /**
     * @return class-string<Publicacion>
     */
    public function modelo(): string
    {
        return match ($this) {
            self::Post => Post::class,
            self::Tutorial => Tutorial::class,
            self::Recurso => Recurso::class,
        };
    }

    public function nuevoModelo(): Publicacion
    {
        $clase = $this->modelo();

        return new $clase;
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Post => 'Publicación',
            self::Tutorial => 'Tutorial',
            self::Recurso => 'Recurso',
        };
    }

    public function etiquetaPlural(): string
    {
        return match ($this) {
            self::Post => 'Publicaciones',
            self::Tutorial => 'Tutoriales',
            self::Recurso => 'Recursos',
        };
    }

    /**
     * Nombre con el que se presenta el tipo en el sitio público, donde
     * "artículos" comunica mejor que el "publicaciones" del panel.
     */
    public function etiquetaSitio(): string
    {
        return match ($this) {
            self::Post => 'Artículos',
            self::Tutorial => 'Tutoriales',
            self::Recurso => 'Recursos',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Post => 'Artículos y proyectos del blog.',
            self::Tutorial => 'Guías paso a paso publicadas en el sitio.',
            self::Recurso => 'Descargables y material de apoyo.',
        };
    }

    public function carpeta(): string
    {
        return match ($this) {
            self::Post => 'posts',
            self::Tutorial => 'tutoriales',
            self::Recurso => 'recursos',
        };
    }

    public function tieneDetalles(): bool
    {
        return $this === self::Recurso;
    }

    public function tieneImportante(): bool
    {
        return $this !== self::Post;
    }
}
