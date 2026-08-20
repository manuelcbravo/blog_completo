<?php

namespace App\Http\Controllers\Blog;

use App\Enums\EstadoContacto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\UpsertContactoRequest;
use App\Http\Resources\Blog\ContactoResource;
use App\Mail\RespuestaContacto;
use App\Models\Contacto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactoController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('busqueda')->trim()->toString();
        $estado = $request->string('estado')->trim()->toString();

        $contactos = Contacto::query()
            ->with('responsable')
            ->buscar($busqueda)
            ->when($estado !== '', fn (Builder $query) => $query->where('estado', $estado))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('blog/contactos/index', [
            'contactos' => ContactoResource::collection($contactos->getCollection())->resolve(),
            'paginacion' => [
                'total' => $contactos->total(),
                'currentPage' => $contactos->currentPage(),
                'lastPage' => $contactos->lastPage(),
                'prevUrl' => $contactos->previousPageUrl(),
                'nextUrl' => $contactos->nextPageUrl(),
                'busqueda' => $busqueda,
            ],
            'filtros' => ['estado' => $estado],
            'estados' => EstadoContacto::opciones(),
            'nuevos' => Contacto::query()->where('estado', EstadoContacto::Nuevo->value)->count(),
        ]);
    }

    public function store(UpsertContactoRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $contacto = Contacto::query()->findOrFail((int) $datos['id']);
        $respuesta = trim((string) ($datos['respuesta'] ?? ''));

        $contacto->estado = EstadoContacto::from($datos['estado']);
        $contacto->leido_at ??= now();

        if ($respuesta !== '') {
            $contacto->respuesta = $respuesta;
            $contacto->respondido_at = now();
            $contacto->respondido_por = $request->user()?->id;
            $contacto->estado = EstadoContacto::Respondido;
        }

        $contacto->save();

        if ($respuesta !== '') {
            Mail::to($contacto->email)->queue(new RespuestaContacto($contacto, $respuesta));
        }

        return back()->with('success', $respuesta !== ''
            ? 'Respuesta enviada correctamente.'
            : 'Mensaje actualizado correctamente.');
    }

    public function destroy(Contacto $contacto): RedirectResponse
    {
        $contacto->delete();

        return back()->with('success', 'Mensaje eliminado correctamente.');
    }
}
