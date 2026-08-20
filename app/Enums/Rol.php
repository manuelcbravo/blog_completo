<?php

namespace App\Enums;

enum Rol: string
{
    case Administrador = 'administrador';
    case Editor = 'editor';

    /**
     * @return list<Permiso>
     */
    public function permisos(): array
    {
        return match ($this) {
            self::Administrador => Permiso::cases(),
            self::Editor => [
                Permiso::BlogPublicacionesGestionar,
                Permiso::BlogTaxonomiasGestionar,
                Permiso::BlogComentariosModerar,
                Permiso::BlogAnaliticaVer,
            ],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Editor => 'Editor',
        };
    }
}
