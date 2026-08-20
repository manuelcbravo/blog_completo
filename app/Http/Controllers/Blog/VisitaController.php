<?php

namespace App\Http\Controllers\Blog;

use App\Enums\SeccionSitio;
use App\Enums\TipoPublicacion;
use App\Http\Controllers\Controller;
use App\Http\Resources\Blog\VistaResource;
use App\Models\Vista;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bitácora de visitas del sitio público: quién abrió qué, desde dónde y con
 * qué navegador. Lee la misma tabla que alimenta las gráficas del tablero,
 * pero renglón por renglón en vez de agregada.
 */
class VisitaController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = $request->string('busqueda')->trim()->toString();
        $tipo = $request->string('tipo')->trim()->toString();
        $origen = $request->string('origen')->trim()->toString();

        $visitas = Vista::query()
            ->when($tipo !== '', fn (Builder $query) => $query->where('tipo', $tipo))
            ->when($origen === 'directo', fn (Builder $query) => $query->whereNull('referer'))
            ->when($origen === 'referido', fn (Builder $query) => $query->whereNotNull('referer'))
            ->when($busqueda !== '', fn (Builder $query) => $query->where(
                fn (Builder $interna) => $interna
                    ->where('ip_address', 'like', "%{$busqueda}%")
                    ->orWhere('referer', 'like', "%{$busqueda}%")
                    ->orWhere('ruta', 'like', "%{$busqueda}%")
                    ->orWhere('user_agent', 'like', "%{$busqueda}%")
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $this->adjuntarTitulos($visitas->getCollection());

        return Inertia::render('blog/visitas/index', [
            'visitas' => VistaResource::collection($visitas->getCollection())->resolve(),
            'paginacion' => [
                'total' => $visitas->total(),
                'currentPage' => $visitas->currentPage(),
                'lastPage' => $visitas->lastPage(),
                'prevUrl' => $visitas->previousPageUrl(),
                'nextUrl' => $visitas->nextPageUrl(),
                'busqueda' => $busqueda,
            ],
            'filtros' => ['tipo' => $tipo, 'origen' => $origen],
            'tipos' => $this->opcionesDeTipo(),
            'resumen' => $this->resumen(),
        ]);
    }

    /**
     * El título de la publicación vive en tres tablas distintas según el tipo,
     * así que se resuelve en un par de consultas por tipo en vez de un join.
     *
     * @param  Collection<int, Vista>  $visitas
     */
    private function adjuntarTitulos(Collection $visitas): void
    {
        foreach (TipoPublicacion::cases() as $tipo) {
            $ids = $visitas
                ->where('tipo', $tipo->value)
                ->pluck('post_id')
                ->filter()
                ->unique();

            if ($ids->isEmpty()) {
                continue;
            }

            $modelo = $tipo->modelo();
            $titulos = $modelo::query()->whereIn('id', $ids->all())->pluck('titulo', 'id');

            foreach ($visitas->where('tipo', $tipo->value) as $visita) {
                $visita->setAttribute(
                    'titulo',
                    $titulos[$visita->post_id] ?? 'Publicación eliminada',
                );
            }
        }

        foreach (SeccionSitio::cases() as $seccion) {
            foreach ($visitas->where('tipo', $seccion->value) as $visita) {
                $visita->setAttribute('titulo', $seccion->etiqueta());
            }
        }
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function opcionesDeTipo(): array
    {
        $opciones = array_map(
            static fn (TipoPublicacion $tipo): array => [
                'value' => $tipo->value,
                'label' => $tipo->etiquetaPlural(),
            ],
            TipoPublicacion::cases(),
        );

        foreach (SeccionSitio::cases() as $seccion) {
            $opciones[] = ['value' => $seccion->value, 'label' => $seccion->etiqueta()];
        }

        return $opciones;
    }

    /**
     * @return array<string, int>
     */
    private function resumen(): array
    {
        $desde = now()->subDays(29)->startOfDay();

        return [
            'total' => Vista::query()->count(),
            'ultimos30' => Vista::query()->where('created_at', '>=', $desde)->count(),
            'ips' => Vista::query()
                ->where('created_at', '>=', $desde)
                ->distinct()
                ->count('ip_address'),
            'referidas' => Vista::query()
                ->where('created_at', '>=', $desde)
                ->whereNotNull('referer')
                ->count(),
        ];
    }
}
