<?php

namespace App\Enums;

enum EstadoSuscriptor: string
{
    case Pendiente = 'pendiente';
    case Confirmado = 'confirmado';
    case Baja = 'baja';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente de confirmar',
            self::Confirmado => 'Confirmado',
            self::Baja => 'Dado de baja',
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
