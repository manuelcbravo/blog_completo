<?php

namespace App\Models;

use App\Enums\EstadoPublicacion;
use App\Enums\TipoPublicacion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $slug
 * @property string $titulo
 * @property string|null $resumen
 * @property string|null $contenido
 * @property string|null $imagen_destacada
 * @property EstadoPublicacion $estado
 * @property CarbonInterface|null $fecha_publicacion
 * @property int $tiempo_lectura
 * @property int $visitas
 * @property bool $importante
 * @property string|null $tags_seo
 * @property string|null $meta_titulo
 * @property string|null $meta_descripcion
 * @property int|null $id_categoria
 * @property int|null $id_autor
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Categoria|null $categoria
 * @property-read User|null $autor
 * @property-read Collection<int, Etiqueta> $etiquetas
 */
abstract class Publicacion extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'titulo',
        'resumen',
        'contenido',
        'imagen_destacada',
        'estado',
        'fecha_publicacion',
        'tiempo_lectura',
        'visitas',
        'importante',
        'tags_seo',
        'meta_titulo',
        'meta_descripcion',
        'og_imagen',
        'id_categoria',
        'id_autor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoPublicacion::class,
            'fecha_publicacion' => 'datetime',
            'importante' => 'boolean',
            'visitas' => 'integer',
            'tiempo_lectura' => 'integer',
        ];
    }

    abstract public function tipo(): TipoPublicacion;

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_autor');
    }

    /**
     * @return BelongsToMany<Etiqueta, $this>
     */
    public function etiquetas(): BelongsToMany
    {
        return $this->belongsToMany(Etiqueta::class, 'post_tags', 'post_id', 'tag_id')
            ->withPivotValue('tipo', $this->tipo()->value)
            ->withTimestamps();
    }

    /**
     * @return HasMany<Comentario, $this>
     */
    public function comentarios(): HasMany
    {
        return $this->hasMany(Comentario::class, 'post_id')
            ->withAttributes(['tipo' => $this->tipo()->value]);
    }

    /**
     * @return HasMany<Vista, $this>
     */
    public function vistas(): HasMany
    {
        return $this->hasMany(Vista::class, 'post_id')
            ->withAttributes(['tipo' => $this->tipo()->value]);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublicadas(Builder $query): void
    {
        $query->where('estado', EstadoPublicacion::Publicado->value);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeBuscar(Builder $query, string $termino): void
    {
        if ($termino === '') {
            return;
        }

        $query->where(function (Builder $query) use ($termino): void {
            $query->whereLike('titulo', "%{$termino}%")
                ->orWhereLike('resumen', "%{$termino}%")
                ->orWhereLike('slug', "%{$termino}%");
        });
    }

    public function imagenUrl(): ?string
    {
        if (blank($this->imagen_destacada)) {
            return null;
        }

        return Storage::disk((string) config('blog.disco'))->url($this->imagen_destacada);
    }

    public function urlPublica(): string
    {
        return match ($this->tipo()) {
            TipoPublicacion::Post => route('publico.articulo', ['slug' => $this->slug]),
            TipoPublicacion::Tutorial => route('publico.tutorial', ['slug' => $this->slug]),
            TipoPublicacion::Recurso => route('publico.recurso', ['slug' => $this->slug]),
        };
    }
}
