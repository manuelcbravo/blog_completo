<?php

namespace App\Http\Controllers\Blog;

use App\Enums\TipoPublicacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\UpsertContenidoRequest;
use App\Http\Resources\Blog\PublicacionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContenidoController extends Controller
{
    public function index(string $tipo, int $publicacion): Response
    {
        $tipoPublicacion = TipoPublicacion::desdeSegmento($tipo);
        $modelo = $tipoPublicacion->modelo();
        $registro = $modelo::query()->with(['categoria', 'autor'])->findOrFail($publicacion);

        return Inertia::render('blog/publicaciones/contenido', [
            'tipo' => [
                'valor' => $tipoPublicacion->value,
                'segmento' => $tipoPublicacion->segmento(),
                'etiqueta' => $tipoPublicacion->etiqueta(),
                'etiquetaPlural' => $tipoPublicacion->etiquetaPlural(),
            ],
            'publicacion' => (new PublicacionResource($registro))->resolve(),
            'contenido' => $registro->contenido ?? '',
        ]);
    }

    public function store(UpsertContenidoRequest $request, string $tipo, int $publicacion): RedirectResponse
    {
        $tipoPublicacion = TipoPublicacion::desdeSegmento($tipo);
        $modelo = $tipoPublicacion->modelo();
        $registro = $modelo::query()->findOrFail($publicacion);

        $registro->contenido = $request->validated('contenido');
        $registro->save();

        return back()->with('success', 'Contenido guardado correctamente.');
    }

    public function imagen(Request $request, string $tipo): JsonResponse
    {
        $tipoPublicacion = TipoPublicacion::desdeSegmento($tipo);

        $request->validate([
            'imagen' => ['required', 'image', 'max:4096'],
        ]);

        $nombre = Str::uuid()->toString().'.'.$request->file('imagen')->extension();
        $disco = (string) config('blog.disco');

        $ruta = $request->file('imagen')->storePubliclyAs(
            $tipoPublicacion->carpeta().'/contenido',
            $nombre,
            $disco,
        );

        if ($ruta === false) {
            return response()->json(['mensaje' => 'No se pudo subir la imagen.'], 500);
        }

        return response()->json(['url' => Storage::disk($disco)->url($ruta)]);
    }
}
