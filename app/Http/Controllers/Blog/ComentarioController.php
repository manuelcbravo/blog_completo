<?php

namespace App\Http\Controllers\Blog;

use App\Enums\EstadoComentario;
use App\Enums\TipoPublicacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\UpsertComentarioRequest;
use App\Http\Resources\Blog\ComentarioResource;
use App\Mail\RespuestaComentario;
use App\Models\Comentario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ComentarioController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('busqueda')->trim()->toString();
        $estado = $request->string('estado')->trim()->toString();
        $tipo = $request->string('tipo')->trim()->toString();

        $comentarios = Comentario::query()
            ->whereNull('parent_id')
            ->with(['respuestas'])
            ->buscar($busqueda)
            ->when($estado !== '', fn (Builder $query) => $query->where('estado', $estado))
            ->when($tipo !== '', fn (Builder $query) => $query->where('tipo', $tipo))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $this->agregarTitulos($comentarios->getCollection());

        return Inertia::render('blog/comentarios/index', [
            'comentarios' => ComentarioResource::collection($comentarios->getCollection())->resolve(),
            'paginacion' => [
                'total' => $comentarios->total(),
                'currentPage' => $comentarios->currentPage(),
                'lastPage' => $comentarios->lastPage(),
                'prevUrl' => $comentarios->previousPageUrl(),
                'nextUrl' => $comentarios->nextPageUrl(),
                'busqueda' => $busqueda,
            ],
            'filtros' => ['estado' => $estado, 'tipo' => $tipo],
            'estados' => EstadoComentario::opciones(),
            'tipos' => array_map(
                fn (TipoPublicacion $tipo): array => [
                    'value' => $tipo->value,
                    'label' => $tipo->etiqueta(),
                ],
                TipoPublicacion::cases(),
            ),
            'pendientes' => Comentario::query()
                ->where('estado', EstadoComentario::Pendiente->value)
                ->count(),
        ]);
    }

    public function store(UpsertComentarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $comentario = Comentario::query()->findOrFail((int) $datos['id']);

        $comentario->forceFill([
            'estado' => $datos['estado'],
            'moderado_at' => now(),
        ])->save();

        $respuesta = trim((string) ($datos['respuesta'] ?? ''));

        if ($respuesta !== '') {
            $usuario = $request->user();

            Comentario::query()->create([
                'post_id' => $comentario->post_id,
                'tipo' => $comentario->tipo->value,
                'user_id' => $usuario->id,
                'parent_id' => $comentario->id,
                'nombre' => $usuario->name,
                'correo' => $usuario->email,
                'contenido' => $respuesta,
                'estado' => EstadoComentario::Aprobado->value,
                'moderado_at' => now(),
            ]);

            if ($datos['notificar'] ?? false) {
                Mail::to($comentario->correo)->queue(new RespuestaComentario($comentario, $respuesta));
            }
        }

        return back()->with('success', 'Comentario actualizado correctamente.');
    }

    public function destroy(Comentario $comentario): RedirectResponse
    {
        $comentario->delete();

        return back()->with('success', 'Comentario eliminado correctamente.');
    }

    /**
     * @param  Collection<int, Comentario>  $comentarios
     */
    private function agregarTitulos(Collection $comentarios): void
    {
        $grupos = $comentarios->groupBy(
            fn (Comentario $comentario): string => $comentario->tipo->value,
        );

        foreach ($grupos as $tipo => $grupo) {
            $modelo = TipoPublicacion::from((string) $tipo)->modelo();

            $titulos = $modelo::query()
                ->withTrashed()
                ->whereIn('id', $grupo->pluck('post_id')->unique()->all())
                ->pluck('titulo', 'id');

            foreach ($grupo as $comentario) {
                $comentario->publicacionTitulo = $titulos[$comentario->post_id] ?? 'Publicación eliminada';
            }
        }
    }
}
