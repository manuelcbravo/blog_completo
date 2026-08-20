<?php

namespace App\Http\Resources\Blog;

use App\Models\RecursoDetalle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin RecursoDetalle
 */
class RecursoDetalleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'detalle' => $this->detalle,
            'recurso_url' => $this->recurso_url,
            'url' => $this->recurso_url === null
                ? null
                : Storage::disk(config('blog.disco'))->url($this->recurso_url),
            'nombre_original' => $this->nombre_original,
            'tamano' => $this->tamano,
            'orden' => $this->orden,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
