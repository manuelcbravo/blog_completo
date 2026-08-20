<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\UpsertCategoriaRequest;
use App\Http\Resources\Blog\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('busqueda')->trim()->toString();

        $categorias = Categoria::query()
            ->withCount('posts')
            ->when($busqueda !== '', fn (Builder $query) => $query->whereLike('nombre', "%{$busqueda}%"))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('blog/categorias/index', [
            'categorias' => CategoriaResource::collection($categorias->getCollection())->resolve(),
            'paginacion' => [
                'total' => $categorias->total(),
                'currentPage' => $categorias->currentPage(),
                'lastPage' => $categorias->lastPage(),
                'prevUrl' => $categorias->previousPageUrl(),
                'nextUrl' => $categorias->nextPageUrl(),
                'busqueda' => $busqueda,
            ],
        ]);
    }

    public function store(UpsertCategoriaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $esEdicion = isset($datos['id']);

        $categoria = $esEdicion
            ? Categoria::query()->findOrFail((int) $datos['id'])
            : new Categoria;

        $categoria->fill([
            'nombre' => $datos['nombre'],
            'slug' => ($datos['slug'] ?? null) ?: Str::slug($datos['nombre']),
            'descripcion' => $datos['descripcion'] ?? null,
        ]);
        $categoria->save();

        return back()->with('success', $esEdicion
            ? 'Categoría actualizada correctamente.'
            : 'Categoría creada correctamente.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        $categoria->delete();

        return back()->with('success', 'Categoría eliminada correctamente.');
    }
}
