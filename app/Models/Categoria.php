<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use SoftDeletes;

    protected $table = 'categories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
    ];

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'id_categoria');
    }

    /**
     * @return HasMany<Tutorial, $this>
     */
    public function tutoriales(): HasMany
    {
        return $this->hasMany(Tutorial::class, 'id_categoria');
    }

    /**
     * @return HasMany<Recurso, $this>
     */
    public function recursos(): HasMany
    {
        return $this->hasMany(Recurso::class, 'id_categoria');
    }
}
