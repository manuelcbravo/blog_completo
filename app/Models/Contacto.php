<?php

namespace App\Models;

use App\Enums\EstadoContacto;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $message
 * @property EstadoContacto $estado
 * @property string|null $respuesta
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonInterface|null $leido_at
 * @property CarbonInterface|null $respondido_at
 * @property int|null $respondido_por
 * @property CarbonInterface|null $created_at
 */
class Contacto extends Model
{
    protected $table = 'contacts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'message',
        'estado',
        'respuesta',
        'ip_address',
        'user_agent',
        'leido_at',
        'respondido_at',
        'respondido_por',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoContacto::class,
            'leido_at' => 'datetime',
            'respondido_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }

    /**
     * @param  Builder<Contacto>  $query
     */
    public function scopeBuscar(Builder $query, string $termino): void
    {
        if ($termino === '') {
            return;
        }

        $query->where(function (Builder $query) use ($termino): void {
            $query->whereLike('name', "%{$termino}%")
                ->orWhereLike('email', "%{$termino}%")
                ->orWhereLike('message', "%{$termino}%");
        });
    }
}
