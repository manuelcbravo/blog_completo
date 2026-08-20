<?php

namespace App\Models;

use App\Enums\TipoPublicacion;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recurso extends Publicacion
{
    protected $table = 'post_recursos';

    public function tipo(): TipoPublicacion
    {
        return TipoPublicacion::Recurso;
    }

    /**
     * @return HasMany<RecursoDetalle, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(RecursoDetalle::class, 'id_recurso')->orderBy('orden');
    }
}
