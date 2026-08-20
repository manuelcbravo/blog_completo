<?php

namespace App\Enums;

enum EstadoContacto: string
{
    case Nuevo = 'nuevo';
    case Leido = 'leido';
    case Respondido = 'respondido';
    case Archivado = 'archivado';

    public function label(): string
    {
        return match ($this) {
            self::Nuevo => 'Nuevo',
            self::Leido => 'Leído',
            self::Respondido => 'Respondido',
            self::Archivado => 'Archivado',
        };
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
