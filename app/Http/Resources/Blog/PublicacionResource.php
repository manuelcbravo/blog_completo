<?php

namespace App\Http\Resources\Blog;

use App\Models\Publicacion;
use App\Models\Recurso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Publicacion
 */
class PublicacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo()->value,
            'slug' => $this->slug,
            'titulo' => $this->titulo,
            'resumen' => $this->resumen,
            'imagen_destacada' => $this->imagen_destacada,
            'imagen_url' => $this->imagenUrl(),
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'fecha_publicacion' => $this->fecha_publicacion?->toISOString(),
            'tiempo_lectura' => $this->tiempo_lectura,
            'visitas' => $this->visitas,
            'importante' => $this->importante,
            'tags_seo' => $this->tags_seo,
            'meta_titulo' => $this->meta_titulo,
            'meta_descripcion' => $this->meta_descripcion,
            'id_categoria' => $this->id_categoria,
            'id_autor' => $this->id_autor,
            'categoria' => $this->relationLoaded('categoria') && $this->categoria !== null
                ? (new CategoriaResource($this->categoria))->resolve()
                : null,
            'autor' => $this->relationLoaded('autor') && $this->autor !== null
                ? (new AutorResource($this->autor))->resolve()
                : null,
            // Siempre arreglos: si la relación no viene cargada, la página
            // recibiría la clave ausente y el tipo de TypeScript mentiría.
            'etiquetas' => $this->relationLoaded('etiquetas')
                ? EtiquetaResource::collection($this->etiquetas->values())->resolve()
                : [],
            'detalles' => $this->resource instanceof Recurso && $this->resource->relationLoaded('detalles')
                ? RecursoDetalleResource::collection($this->resource->detalles->values())->resolve()
                : [],
            'comentarios' => (int) ($this->comentarios_count ?? 0),
            'url_publica' => $this->urlPublica(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
