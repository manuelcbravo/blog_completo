<?php

namespace App\Models;

use App\Enums\TipoPublicacion;

class Post extends Publicacion
{
    protected $table = 'posts';

    public function tipo(): TipoPublicacion
    {
        return TipoPublicacion::Post;
    }
}
