<?php

namespace App\Http\Resources\Blog;

use App\Models\Comentario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comentario
 */
class ComentarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'tipo' => $this->tipo->value,
            'tipo_label' => $this->tipo->etiqueta(),
            'parent_id' => $this->parent_id,
            'nombre' => $this->nombre,
            'correo' => $this->correo,
            'contenido' => $this->contenido,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'publicacion_titulo' => $this->resource->publicacionTitulo,
            'respuestas' => $this->relationLoaded('respuestas')
                ? ComentarioResource::collection($this->respuestas->values())->resolve()
                : [],
            'moderado_at' => $this->moderado_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
