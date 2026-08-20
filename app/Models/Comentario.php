<?php

namespace App\Models;

use App\Enums\EstadoComentario;
use App\Enums\TipoPublicacion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $post_id
 * @property TipoPublicacion $tipo
 * @property int|null $user_id
 * @property int|null $parent_id
 * @property string $nombre
 * @property string $correo
 * @property string $contenido
 * @property EstadoComentario $estado
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonInterface|null $moderado_at
 * @property CarbonInterface|null $created_at
 */
class Comentario extends Model
{
    protected $table = 'post_comments';

    public ?string $publicacionTitulo = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
        'tipo',
        'user_id',
        'parent_id',
        'nombre',
        'correo',
        'contenido',
        'estado',
        'ip_address',
        'user_agent',
        'moderado_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoPublicacion::class,
            'estado' => EstadoComentario::class,
            'moderado_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function respuestas(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function publicacion(): ?Publicacion
    {
        $clase = $this->tipo->modelo();

        return $clase::query()->find($this->post_id);
    }

    /**
     * @param  Builder<Comentario>  $query
     */
    public function scopeBuscar(Builder $query, string $termino): void
    {
        if ($termino === '') {
            return;
        }

        $query->where(function (Builder $query) use ($termino): void {
            $query->whereLike('nombre', "%{$termino}%")
                ->orWhereLike('correo', "%{$termino}%")
                ->orWhereLike('contenido', "%{$termino}%");
        });
    }
}
