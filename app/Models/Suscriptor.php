<?php

namespace App\Models;

use App\Enums\EstadoSuscriptor;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $email
 * @property string|null $nombre
 * @property EstadoSuscriptor $estado
 * @property string|null $token
 * @property string|null $origen
 * @property string|null $ip_address
 * @property CarbonInterface|null $confirmado_at
 * @property CarbonInterface|null $baja_at
 * @property CarbonInterface|null $created_at
 */
class Suscriptor extends Model
{
    protected $table = 'subscribers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'nombre',
        'estado',
        'token',
        'origen',
        'ip_address',
        'confirmado_at',
        'baja_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoSuscriptor::class,
            'confirmado_at' => 'datetime',
            'baja_at' => 'datetime',
        ];
    }

    public static function nuevoToken(): string
    {
        return Str::random(48);
    }

    public function confirmar(): void
    {
        $this->forceFill([
            'estado' => EstadoSuscriptor::Confirmado,
            'confirmado_at' => now(),
            'baja_at' => null,
        ])->save();
    }

    public function darDeBaja(): void
    {
        $this->forceFill([
            'estado' => EstadoSuscriptor::Baja,
            'baja_at' => now(),
        ])->save();
    }

    /**
     * @param  Builder<Suscriptor>  $query
     */
    public function scopeConfirmados(Builder $query): void
    {
        $query->where('estado', EstadoSuscriptor::Confirmado->value);
    }

    /**
     * @param  Builder<Suscriptor>  $query
     */
    public function scopeBuscar(Builder $query, string $termino): void
    {
        if ($termino === '') {
            return;
        }

        $query->where(function (Builder $query) use ($termino): void {
            $query->whereLike('email', "%{$termino}%")
                ->orWhereLike('nombre', "%{$termino}%");
        });
    }
}
