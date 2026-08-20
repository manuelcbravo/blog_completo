<?php

namespace App\Http\Controllers\Publico;

use App\Actions\Blog\RegistrarComentario;
use App\Actions\Blog\RegistrarContacto;
use App\Actions\Blog\RegistrarSuscriptor;
use App\Enums\EstadoComentario;
use App\Enums\SeccionSitio;
use App\Enums\TipoPublicacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publico\ComentarRequest;
use App\Http\Requests\Publico\ContactarRequest;
use App\Http\Requests\Publico\SuscribirRequest;
use App\Models\Categoria;
use App\Models\Comentario;
use App\Models\Post;
use App\Models\Publicacion;
use App\Models\Vista;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SitioController extends Controller
{
    public function home(): View
    {
        $destacada = Post::query()
            ->publicadas()
            ->with(['categoria', 'autor'])
            ->orderByDesc('importante')
            ->latest('fecha_publicacion')
            ->first();

        $recientes = Post::query()
            ->publicadas()
            ->with(['categoria', 'autor'])
            ->when($destacada !== null, fn (Builder $query) => $query->whereKeyNot($destacada->id))
            ->latest('fecha_publicacion')
            ->limit(6)
            ->get();

        return view('publico.home', [
            'destacada' => $destacada,
            'recientes' => $recientes,
            'masLeidas' => $this->masLeidas(),
            'ultimosTutoriales' => $this->ultimos(TipoPublicacion::Tutorial, 3),
            'ultimosRecursos' => $this->ultimos(TipoPublicacion::Recurso, 3),
        ]);
    }

    public function articulos(Request $request): View
    {
        return $this->listado($request, TipoPublicacion::Post);
    }

    public function tutoriales(Request $request): View
    {
        return $this->listado($request, TipoPublicacion::Tutorial);
    }

    public function recursos(Request $request): View
    {
        return $this->listado($request, TipoPublicacion::Recurso);
    }

    public function articulo(Request $request, string $slug): View
    {
        return $this->mostrar($request, TipoPublicacion::Post, $slug);
    }

    public function tutorial(Request $request, string $slug): View
    {
        return $this->mostrar($request, TipoPublicacion::Tutorial, $slug);
    }

    public function recurso(Request $request, string $slug): View
    {
        return $this->mostrar($request, TipoPublicacion::Recurso, $slug);
    }

    private function listado(Request $request, TipoPublicacion $tipo): View
    {
        $modelo = $tipo->modelo();

        $publicaciones = $modelo::query()
            ->publicadas()
            ->with(['categoria', 'autor'])
            ->latest('fecha_publicacion')
            ->paginate(12);

        return view('publico.listado', [
            'tipo' => $tipo,
            'publicaciones' => $publicaciones,
            'masLeidas' => $this->masLeidas(),
        ]);
    }

    private function mostrar(Request $request, TipoPublicacion $tipo, string $slug): View
    {
        $modelo = $tipo->modelo();

        $publicacion = $modelo::query()
            ->publicadas()
            ->with(['categoria', 'autor', 'etiquetas'])
            ->when($tipo->tieneDetalles(), fn (Builder $query) => $query->with('detalles'))
            ->where('slug', $slug)
            ->firstOrFail();

        $this->registrarVista($request, $publicacion);

        $relacionadas = $modelo::query()
            ->publicadas()
            ->with('categoria')
            ->whereKeyNot($publicacion->id)
            ->when(
                $publicacion->id_categoria !== null,
                fn (Builder $query) => $query->where('id_categoria', $publicacion->id_categoria),
            )
            ->latest('fecha_publicacion')
            ->limit(3)
            ->get();

        return view('publico.articulo', [
            'tipo' => $tipo,
            'publicacion' => $publicacion,
            'relacionadas' => $relacionadas,
            'comentarios' => $this->comentariosDe($publicacion),
        ]);
    }

    public function categoria(string $slug): View
    {
        $categoria = Categoria::query()->where('slug', $slug)->firstOrFail();

        $publicaciones = Post::query()
            ->publicadas()
            ->with(['categoria', 'autor'])
            ->where('id_categoria', $categoria->id)
            ->latest('fecha_publicacion')
            ->paginate(12);

        return view('publico.categoria', [
            'categoria' => $categoria,
            'publicaciones' => $publicaciones,
        ]);
    }

    public function buscar(Request $request): View
    {
        $termino = $request->string('q')->trim()->toString();
        $resultados = collect();

        if ($termino !== '') {
            foreach (TipoPublicacion::cases() as $tipo) {
                $modelo = $tipo->modelo();

                $resultados = $resultados->concat(
                    $modelo::query()
                        ->publicadas()
                        ->with('categoria')
                        ->buscar($termino)
                        ->latest('fecha_publicacion')
                        ->limit(20)
                        ->get(),
                );
            }

            $resultados = $resultados
                ->sortByDesc(fn (Publicacion $publicacion) => $publicacion->fecha_publicacion)
                ->values();
        }

        return view('publico.buscar', [
            'termino' => $termino,
            'resultados' => $resultados,
            'sugerencias' => Categoria::query()->orderBy('nombre')->limit(6)->get(),
        ]);
    }

    public function newsletter(): View
    {
        return view('publico.newsletter', [
            'ultima' => Post::query()
                ->publicadas()
                ->with('categoria')
                ->latest('fecha_publicacion')
                ->first(),
        ]);
    }

    public function suscribir(SuscribirRequest $request, RegistrarSuscriptor $registrar): RedirectResponse
    {
        $datos = $request->validated();

        $resultado = $registrar(
            $datos['email'],
            $datos['nombre'] ?? null,
            $datos['origen'] ?? 'sitio público',
            $request->ip(),
        );

        return back()->with('suscripcion', $resultado['yaEstaba']
            ? 'Ese correo ya estaba suscrito.'
            : 'Listo. Revisa tu correo para confirmar la suscripción.');
    }

    public function comentar(
        ComentarRequest $request,
        RegistrarComentario $registrar,
    ): RedirectResponse {
        $datos = $request->validated();
        $tipo = TipoPublicacion::from($datos['tipo']);
        $modelo = $tipo->modelo();

        $publicacion = $modelo::query()
            ->publicadas()
            ->findOrFail((int) $datos['post_id']);

        $registrar($publicacion, [
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'contenido' => $datos['contenido'],
            'parent_id' => $datos['parent_id'] ?? null,
        ], $request->ip(), $request->userAgent());

        return back()
            ->with('comentario', config('blog.comentarios_moderacion')
                ? 'Gracias. Publico tu comentario en cuanto lo revise.'
                : 'Gracias por comentar.')
            ->withFragment('comentarios');
    }

    public function contactar(ContactarRequest $request, RegistrarContacto $registrar): RedirectResponse
    {
        $datos = $request->validated();

        $registrar([
            'nombre' => (string) $datos['nombre'],
            'email' => (string) $datos['email'],
            'mensaje' => (string) $datos['mensaje'],
        ], $request->ip(), $request->userAgent());

        return back()
            ->with('contacto', 'Gracias por escribir. Te respondo en menos de 24 horas.')
            ->withFragment('contacto');
    }

    public function sobre(): View
    {
        return view('publico.sobre');
    }

    public function privacidad(): View
    {
        return view('publico.privacidad');
    }

    public function proyectos(Request $request): View
    {
        $this->registrarVistaPagina($request, SeccionSitio::Proyectos);

        return view('publico.proyectos', [
            'proyectos' => config('proyectos'),
        ]);
    }

    public function autor(Request $request): View
    {
        $this->registrarVistaPagina($request, SeccionSitio::Autor);

        $publicaciones = Post::query()
            ->publicadas()
            ->with('categoria')
            ->latest('fecha_publicacion')
            ->limit(12)
            ->get();

        $total = 0;

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();
            $total += $modelo::query()->publicadas()->count();
        }

        return view('publico.autor', [
            'publicaciones' => $publicaciones,
            'totalPublicaciones' => $total,
            'ficha' => config('autor'),
        ]);
    }

    /**
     * @return Collection<int, Post>
     */
    private function masLeidas(int $limite = 4)
    {
        return Post::query()
            ->publicadas()
            ->orderByDesc('visitas')
            ->limit($limite)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Publicacion>
     */
    private function ultimos(TipoPublicacion $tipo, int $limite)
    {
        $modelo = $tipo->modelo();

        return $modelo::query()
            ->publicadas()
            ->with('categoria')
            ->latest('fecha_publicacion')
            ->limit($limite)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Comentario>
     */
    private function comentariosDe(Publicacion $publicacion)
    {
        return Comentario::query()
            ->where('tipo', $publicacion->tipo()->value)
            ->where('post_id', $publicacion->id)
            ->where('estado', EstadoComentario::Aprobado->value)
            ->whereNull('parent_id')
            ->with(['respuestas' => fn ($query) => $query
                ->where('estado', EstadoComentario::Aprobado->value)
                ->oldest(),
            ])
            ->oldest()
            ->get();
    }

    private function registrarVistaPagina(Request $request, SeccionSitio $seccion): void
    {
        $clave = "vista:pagina:{$seccion->value}";

        if ($request->session()->has($clave)) {
            return;
        }

        $request->session()->put($clave, true);

        Vista::query()->create([
            'post_id' => null,
            'tipo' => $seccion->value,
            'ruta' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'referer' => $request->header('referer'),
            'session_id' => $request->session()->getId(),
        ]);
    }

    private function registrarVista(Request $request, Publicacion $publicacion): void
    {
        $clave = "vista:{$publicacion->tipo()->value}:{$publicacion->id}";

        if ($request->session()->has($clave)) {
            return;
        }

        $request->session()->put($clave, true);

        Vista::query()->create([
            'post_id' => $publicacion->id,
            'tipo' => $publicacion->tipo()->value,
            'ruta' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'referer' => $request->header('referer'),
            'session_id' => $request->session()->getId(),
        ]);

        $publicacion->increment('visitas');
    }
}
