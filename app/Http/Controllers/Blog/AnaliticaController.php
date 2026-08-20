<?php

namespace App\Http\Controllers\Blog;

use App\Enums\EstadoComentario;
use App\Enums\EstadoContacto;
use App\Enums\EstadoPublicacion;
use App\Enums\EstadoSuscriptor;
use App\Enums\TipoPublicacion;
use App\Http\Controllers\Controller;
use App\Models\Comentario;
use App\Models\Contacto;
use App\Models\Suscriptor;
use App\Models\Vista;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnaliticaController extends Controller
{
    private const DIAS = 30;

    public function index(Request $request): Response
    {
        if ($request->user()?->cannot('blog.analitica.ver')) {
            return Inertia::render('dashboard', ['analitica' => null]);
        }

        $desde = now()->subDays(self::DIAS - 1)->startOfDay();

        return Inertia::render('dashboard', [
            'analitica' => [
                'resumen' => $this->resumen($desde),
                'serieVistas' => $this->serieVistas($desde),
                'porTipo' => $this->porTipo($desde),
                'top' => $this->top($desde),
                'ultimas' => $this->ultimas(),
                'programadas' => $this->programadas(),
            ],
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function resumen(CarbonInterface $desde): array
    {
        $publicadas = 0;
        $borradores = 0;

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();
            $publicadas += $modelo::query()->where('estado', EstadoPublicacion::Publicado->value)->count();
            $borradores += $modelo::query()->whereNot('estado', EstadoPublicacion::Publicado->value)->count();
        }

        return [
            'publicadas' => $publicadas,
            'borradores' => $borradores,
            'vistas' => Vista::query()->where('created_at', '>=', $desde)->count(),
            'suscriptores' => Suscriptor::query()->confirmados()->count(),
            'suscriptoresNuevos' => Suscriptor::query()->where('created_at', '>=', $desde)->count(),
            'comentariosPendientes' => Comentario::query()
                ->where('estado', EstadoComentario::Pendiente->value)
                ->count(),
            'contactosNuevos' => Contacto::query()
                ->where('estado', EstadoContacto::Nuevo->value)
                ->count(),
            'suscriptoresPendientes' => Suscriptor::query()
                ->where('estado', EstadoSuscriptor::Pendiente->value)
                ->count(),
            'programadas' => $this->contarProgramadas(),
        ];
    }

    private function contarProgramadas(): int
    {
        $total = 0;

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();
            $total += $modelo::query()
                ->where('estado', EstadoPublicacion::Programado->value)
                ->count();
        }

        return $total;
    }

    /**
     * @return list<array{fecha: string, vistas: int, post: int, tutorial: int, recurso: int}>
     */
    private function serieVistas(CarbonInterface $desde): array
    {
        $conteos = Vista::query()
            ->where('created_at', '>=', $desde)
            ->select([
                DB::raw('date(created_at) as dia'),
                'tipo',
                DB::raw('count(*) as total'),
            ])
            ->groupBy('dia', 'tipo')
            ->get()
            ->groupBy(fn (Vista $vista): string => (string) $vista->getAttribute('dia'));

        $serie = [];

        for ($i = 0; $i < self::DIAS; $i++) {
            $fecha = $desde->copy()->addDays($i)->toDateString();
            $delDia = $conteos->get($fecha);

            $porTipo = static fn (TipoPublicacion $tipo): int => (int) (
                $delDia?->firstWhere('tipo', $tipo)?->getAttribute('total') ?? 0
            );

            $post = $porTipo(TipoPublicacion::Post);
            $tutorial = $porTipo(TipoPublicacion::Tutorial);
            $recurso = $porTipo(TipoPublicacion::Recurso);

            $serie[] = [
                'fecha' => $fecha,
                'vistas' => $post + $tutorial + $recurso,
                'post' => $post,
                'tutorial' => $tutorial,
                'recurso' => $recurso,
            ];
        }

        return $serie;
    }

    /**
     * @return list<array{tipo: string, etiqueta: string, total: int, vistas: int}>
     */
    private function porTipo(CarbonInterface $desde): array
    {
        return array_map(function (TipoPublicacion $tipo) use ($desde): array {
            $modelo = $tipo->modelo();

            return [
                'tipo' => $tipo->value,
                'etiqueta' => $tipo->etiquetaPlural(),
                'total' => $modelo::query()->count(),
                'vistas' => Vista::query()
                    ->where('tipo', $tipo->value)
                    ->where('created_at', '>=', $desde)
                    ->count(),
            ];
        }, TipoPublicacion::cases());
    }

    /**
     * @return list<array{titulo: string, tipo: string, vistas: int}>
     */
    private function top(CarbonInterface $desde): array
    {
        $filas = [];

        foreach (TipoPublicacion::cases() as $tipo) {
            $conteos = Vista::query()
                ->where('tipo', $tipo->value)
                ->where('created_at', '>=', $desde)
                ->select(['post_id', DB::raw('count(*) as total')])
                ->groupBy('post_id')
                ->orderByDesc('total')
                ->limit(5)
                ->pluck('total', 'post_id');

            if ($conteos->isEmpty()) {
                continue;
            }

            $modelo = $tipo->modelo();
            $titulos = $modelo::query()
                ->whereIn('id', $conteos->keys()->all())
                ->pluck('titulo', 'id');

            foreach ($conteos as $id => $total) {
                $filas[] = [
                    'titulo' => $titulos[$id] ?? 'Publicación eliminada',
                    'tipo' => $tipo->etiqueta(),
                    'vistas' => (int) $total,
                ];
            }
        }

        usort($filas, fn (array $a, array $b): int => $b['vistas'] <=> $a['vistas']);

        return array_slice($filas, 0, 8);
    }

    /**
     * @return list<array{titulo: string, tipo: string, segmento: string, fecha: string|null, sinFecha: bool}>
     */
    private function programadas(): array
    {
        $filas = [];

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();

            $pendientes = $modelo::query()
                ->where('estado', EstadoPublicacion::Programado->value)
                ->orderBy('fecha_publicacion')
                ->get();

            foreach ($pendientes as $publicacion) {
                $filas[] = [
                    'titulo' => $publicacion->titulo,
                    'tipo' => $tipo->etiqueta(),
                    'segmento' => $tipo->segmento(),
                    'fecha' => $publicacion->fecha_publicacion?->toISOString(),
                    'sinFecha' => $publicacion->fecha_publicacion === null,
                ];
            }
        }

        usort($filas, fn (array $a, array $b): int => strcmp((string) $a['fecha'], (string) $b['fecha']));

        return array_slice($filas, 0, 8);
    }

    /**
     * @return list<array{titulo: string, tipo: string, estado: string, fecha: string|null}>
     */
    private function ultimas(): array
    {
        $filas = [];

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();

            foreach ($modelo::query()->latest()->limit(5)->get() as $publicacion) {
                $filas[] = [
                    'titulo' => $publicacion->titulo,
                    'tipo' => $tipo->etiqueta(),
                    'estado' => $publicacion->estado->label(),
                    'fecha' => $publicacion->created_at?->toISOString(),
                ];
            }
        }

        usort($filas, fn (array $a, array $b): int => strcmp((string) $b['fecha'], (string) $a['fecha']));

        return array_slice($filas, 0, 8);
    }
}
