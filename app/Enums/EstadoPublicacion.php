<?php

namespace App\Enums;

enum EstadoPublicacion: string
{
    case Borrador = 'borrador';
    case Revision = 'revision';
    case Programado = 'programado';
    case Publicado = 'publicado';
    case Abajo = 'abajo';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Revision => 'En revisión',
            self::Programado => 'Programado',
            self::Publicado => 'Publicado',
            self::Abajo => 'Fuera de línea',
        };
    }

    public function esPublico(): bool
    {
        return $this === self::Publicado;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function opciones(): array
    {
        return array_map(
            fn (self $estado): array => ['value' => $estado->value, 'label' => $estado->label()],
            self::cases(),
        );
    }
}
