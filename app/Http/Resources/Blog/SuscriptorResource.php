<?php

namespace App\Http\Resources\Blog;

use App\Models\Suscriptor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Suscriptor
 */
class SuscriptorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'nombre' => $this->nombre,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'origen' => $this->origen,
            'confirmado_at' => $this->confirmado_at?->toISOString(),
            'baja_at' => $this->baja_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
