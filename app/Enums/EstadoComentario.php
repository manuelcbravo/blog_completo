<?php

namespace App\Enums;

enum EstadoComentario: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobado => 'Aprobado',
            self::Rechazado => 'Rechazado',
            self::Spam => 'Spam',
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
