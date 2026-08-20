<?php

namespace App\Http\Controllers\Blog;

use App\Enums\EstadoPublicacion;
use App\Enums\TipoPublicacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\UpsertEstadoRequest;
use Illuminate\Http\RedirectResponse;

class EstadoController extends Controller
{
    public function store(UpsertEstadoRequest $request, string $tipo, int $publicacion): RedirectResponse
    {
        $tipoPublicacion = TipoPublicacion::desdeSegmento($tipo);
        $modelo = $tipoPublicacion->modelo();
        $registro = $modelo::query()->findOrFail($publicacion);

        $datos = $request->validated();
        $estado = EstadoPublicacion::from($datos['estado']);
        $anterior = $registro->estado;

        $registro->estado = $estado;

        if (array_key_exists('fecha_publicacion', $datos)) {
            $registro->fecha_publicacion = $datos['fecha_publicacion'];
        }

        if ($estado === EstadoPublicacion::Programado && $registro->fecha_publicacion === null) {
            $registro->fecha_publicacion = now();
        }

        $registro->save();

        $aviso = $estado === EstadoPublicacion::Publicado && $anterior !== EstadoPublicacion::Publicado
            ? 'Publicado. Se encoló el aviso a los suscriptores.'
            : 'Estado actualizado a '.$estado->label().'.';

        return back()->with('success', $aviso);
    }
}
