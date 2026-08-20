<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\UpsertEtiquetaRequest;
use App\Http\Resources\Blog\EtiquetaResource;
use App\Models\Etiqueta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EtiquetaController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('busqueda')->trim()->toString();

        $etiquetas = Etiqueta::query()
            ->when($busqueda !== '', fn (Builder $query) => $query->whereLike('nombre', "%{$busqueda}%"))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('blog/etiquetas/index', [
            'etiquetas' => EtiquetaResource::collection($etiquetas->getCollection())->resolve(),
            'paginacion' => [
                'total' => $etiquetas->total(),
                'currentPage' => $etiquetas->currentPage(),
                'lastPage' => $etiquetas->lastPage(),
                'prevUrl' => $etiquetas->previousPageUrl(),
                'nextUrl' => $etiquetas->nextPageUrl(),
                'busqueda' => $busqueda,
            ],
        ]);
    }

    public function store(UpsertEtiquetaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $esEdicion = isset($datos['id']);

        $etiqueta = $esEdicion
            ? Etiqueta::query()->findOrFail((int) $datos['id'])
            : new Etiqueta;

        $etiqueta->fill([
            'nombre' => $datos['nombre'],
            'slug' => ($datos['slug'] ?? null) ?: Str::slug($datos['nombre']),
        ]);
        $etiqueta->save();

        return back()->with('success', $esEdicion
            ? 'Etiqueta actualizada correctamente.'
            : 'Etiqueta creada correctamente.');
    }

    public function destroy(Etiqueta $etiqueta): RedirectResponse
    {
        $etiqueta->delete();

        return back()->with('success', 'Etiqueta eliminada correctamente.');
    }
}
