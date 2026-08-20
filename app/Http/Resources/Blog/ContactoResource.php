<?php

namespace App\Http\Resources\Blog;

use App\Models\Contacto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contacto
 */
class ContactoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'email' => $this->email,
            'mensaje' => $this->message,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'respuesta' => $this->respuesta,
            'responsable' => $this->relationLoaded('responsable') && $this->responsable !== null
                ? (new AutorResource($this->responsable))->resolve()
                : null,
            'leido_at' => $this->leido_at?->toISOString(),
            'respondido_at' => $this->respondido_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
