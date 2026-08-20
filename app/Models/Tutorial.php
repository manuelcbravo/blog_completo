<?php

namespace App\Models;

use App\Enums\TipoPublicacion;

class Tutorial extends Publicacion
{
    protected $table = 'post_tutorials';

    public function tipo(): TipoPublicacion
    {
        return TipoPublicacion::Tutorial;
    }
}
