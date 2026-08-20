<?php

namespace App\Http\Resources\Blog;

use App\Models\Vista;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vista
 */
class VistaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $seccion = $this->seccion();
        $tipo = $this->tipoPublicacion();

        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'tipo_label' => $seccion?->etiqueta() ?? $tipo?->etiqueta() ?? $this->tipo,
            'titulo' => $this->getAttribute('titulo'),
            'ruta' => $this->ruta,
            'ip' => $this->ip_address,
            'navegador' => $this->navegador(),
            'user_agent' => $this->user_agent,
            'referer' => $this->referer,
            'origen' => $this->origen(),
            'session_id' => $this->session_id,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
