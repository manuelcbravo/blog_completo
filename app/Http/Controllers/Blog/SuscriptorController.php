<?php

namespace App\Http\Controllers\Blog;

use App\Enums\EstadoSuscriptor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\UpsertSuscriptorRequest;
use App\Http\Resources\Blog\SuscriptorResource;
use App\Models\Suscriptor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuscriptorController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('busqueda')->trim()->toString();
        $estado = $request->string('estado')->trim()->toString();

        $suscriptores = Suscriptor::query()
            ->buscar($busqueda)
            ->when($estado !== '', fn (Builder $query) => $query->where('estado', $estado))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('blog/suscriptores/index', [
            'suscriptores' => SuscriptorResource::collection($suscriptores->getCollection())->resolve(),
            'paginacion' => [
                'total' => $suscriptores->total(),
                'currentPage' => $suscriptores->currentPage(),
                'lastPage' => $suscriptores->lastPage(),
                'prevUrl' => $suscriptores->previousPageUrl(),
                'nextUrl' => $suscriptores->nextPageUrl(),
                'busqueda' => $busqueda,
            ],
            'filtros' => ['estado' => $estado],
            'estados' => EstadoSuscriptor::opciones(),
            'resumen' => [
                'total' => Suscriptor::query()->count(),
                'confirmados' => Suscriptor::query()->confirmados()->count(),
                'pendientes' => Suscriptor::query()
                    ->where('estado', EstadoSuscriptor::Pendiente->value)
                    ->count(),
            ],
        ]);
    }

    public function store(UpsertSuscriptorRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $esEdicion = isset($datos['id']);

        $suscriptor = $esEdicion
            ? Suscriptor::query()->findOrFail((int) $datos['id'])
            : new Suscriptor;

        $suscriptor->fill([
            'email' => $datos['email'],
            'nombre' => $datos['nombre'] ?? null,
            'estado' => $datos['estado'],
        ]);

        if ($suscriptor->token === null) {
            $suscriptor->token = Suscriptor::nuevoToken();
        }

        if ($datos['estado'] === EstadoSuscriptor::Confirmado->value && $suscriptor->confirmado_at === null) {
            $suscriptor->confirmado_at = now();
        }

        if ($datos['estado'] === EstadoSuscriptor::Baja->value && $suscriptor->baja_at === null) {
            $suscriptor->baja_at = now();
        }

        $suscriptor->origen ??= 'alta manual';
        $suscriptor->save();

        return back()->with('success', $esEdicion
            ? 'Suscriptor actualizado correctamente.'
            : 'Suscriptor creado correctamente.');
    }

    public function destroy(Suscriptor $suscriptor): RedirectResponse
    {
        $suscriptor->delete();

        return back()->with('success', 'Suscriptor eliminado correctamente.');
    }
}
