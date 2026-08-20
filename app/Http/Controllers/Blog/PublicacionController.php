<?php

namespace App\Http\Controllers\Blog;

use App\Enums\EstadoPublicacion;
use App\Enums\TipoPublicacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\UpsertPublicacionRequest;
use App\Http\Resources\Blog\AutorResource;
use App\Http\Resources\Blog\CategoriaResource;
use App\Http\Resources\Blog\EtiquetaResource;
use App\Http\Resources\Blog\PublicacionResource;
use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicacionController extends Controller
{
    public function index(Request $request, string $tipo): Response
    {
        $tipoPublicacion = TipoPublicacion::desdeSegmento($tipo);
        $busqueda = $request->string('busqueda')->trim()->toString();
        $estado = $request->string('estado')->trim()->toString();
        $modelo = $tipoPublicacion->modelo();

        $publicaciones = $modelo::query()
            ->with(['categoria', 'autor', 'etiquetas'])
            ->withCount('comentarios')
            ->when($tipoPublicacion->tieneDetalles(), fn (Builder $query) => $query->with('detalles'))
            ->buscar($busqueda)
            ->when($estado !== '', fn (Builder $query) => $query->where('estado', $estado))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('blog/publicaciones/index', [
            'tipo' => [
                'valor' => $tipoPublicacion->value,
                'segmento' => $tipoPublicacion->segmento(),
                'etiqueta' => $tipoPublicacion->etiqueta(),
                'etiquetaPlural' => $tipoPublicacion->etiquetaPlural(),
                'descripcion' => $tipoPublicacion->descripcion(),
                'tieneDetalles' => $tipoPublicacion->tieneDetalles(),
            ],
            'publicaciones' => PublicacionResource::collection($publicaciones->getCollection())->resolve(),
            'paginacion' => [
                'total' => $publicaciones->total(),
                'currentPage' => $publicaciones->currentPage(),
                'lastPage' => $publicaciones->lastPage(),
                'prevUrl' => $publicaciones->previousPageUrl(),
                'nextUrl' => $publicaciones->nextPageUrl(),
                'busqueda' => $busqueda,
            ],
            'filtros' => ['estado' => $estado],
            'categorias' => CategoriaResource::collection(
                Categoria::query()->orderBy('nombre')->get(),
            )->resolve(),
            'etiquetas' => EtiquetaResource::collection(
                Etiqueta::query()->orderBy('nombre')->get(),
            )->resolve(),
            'autores' => AutorResource::collection(
                User::query()->orderBy('name')->get(),
            )->resolve(),
            'estados' => EstadoPublicacion::opciones(),
        ]);
    }

    public function store(UpsertPublicacionRequest $request, string $tipo): RedirectResponse
    {
        $tipoPublicacion = TipoPublicacion::desdeSegmento($tipo);
        $datos = $request->validated();
        $esEdicion = isset($datos['id']);
        $modelo = $tipoPublicacion->modelo();

        $publicacion = $esEdicion
            ? $modelo::query()->findOrFail((int) $datos['id'])
            : $tipoPublicacion->nuevoModelo();

        $publicacion->fill([
            'titulo' => $datos['titulo'],
            'slug' => $datos['slug'] ?? null,
            'resumen' => $datos['resumen'] ?? null,
            'tags_seo' => $datos['tags_seo'],
            'estado' => $datos['estado'],
            'fecha_publicacion' => $datos['fecha_publicacion'] ?? null,
            'importante' => $datos['importante'] ?? false,
            'id_categoria' => $datos['id_categoria'] ?? null,
            'id_autor' => $datos['id_autor'],
            'meta_titulo' => $datos['meta_titulo'] ?? null,
            'meta_descripcion' => $datos['meta_descripcion'] ?? null,
        ]);

        if ($request->hasFile('imagen')) {
            if ($publicacion->imagen_destacada !== null) {
                Storage::disk(config('blog.disco'))->delete($publicacion->imagen_destacada);
            }

            $ruta = $request->file('imagen')
                ->storePublicly($tipoPublicacion->carpeta(), config('blog.disco'));

            if ($ruta === false) {
                return back()->with('error', 'No se pudo subir la imagen destacada.');
            }

            $publicacion->imagen_destacada = $ruta;
        } elseif ($request->boolean('eliminar_imagen') && $publicacion->imagen_destacada !== null) {
            Storage::disk(config('blog.disco'))->delete($publicacion->imagen_destacada);
            $publicacion->imagen_destacada = null;
        }

        $publicacion->save();
        $publicacion->etiquetas()->sync($datos['etiquetas'] ?? []);

        return back()->with('success', $esEdicion
            ? $tipoPublicacion->etiqueta().' actualizada correctamente.'
            : $tipoPublicacion->etiqueta().' creada correctamente.');
    }

    public function destroy(string $tipo, int $publicacion): RedirectResponse
    {
        $tipoPublicacion = TipoPublicacion::desdeSegmento($tipo);
        $modelo = $tipoPublicacion->modelo();
        $registro = $modelo::query()->findOrFail($publicacion);

        $registro->delete();

        return back()->with('success', $tipoPublicacion->etiqueta().' eliminada correctamente.');
    }
}
