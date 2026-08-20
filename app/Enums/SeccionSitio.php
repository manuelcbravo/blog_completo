<?php

namespace App\Enums;

/**
 * Páginas del sitio público que no son una publicación y aun así se miden:
 * la ficha del autor y el listado de proyectos. Sus visitas se guardan en la
 * misma tabla que las de las publicaciones, con `post_id` nulo y estos valores
 * en la columna `tipo`, que por eso no se castea a TipoPublicacion.
 */
enum SeccionSitio: string
{
    case Autor = 'autor';
    case Proyectos = 'proyectos';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_map(static fn (self $seccion): string => $seccion->value, self::cases());
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Autor => 'Ficha del autor',
            self::Proyectos => 'Proyectos',
        };
    }

    public function ruta(): string
    {
        return match ($this) {
            self::Autor => '/manuel',
            self::Proyectos => '/proyectos',
        };
    }
}
