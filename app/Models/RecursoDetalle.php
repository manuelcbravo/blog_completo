<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoDetalle extends Model
{
    protected $table = 'post_recursos_detalles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_recurso',
        'detalle',
        'recurso_url',
        'nombre_original',
        'tamano',
        'orden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tamano' => 'integer',
            'orden' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Recurso, $this>
     */
    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class, 'id_recurso');
    }
}
