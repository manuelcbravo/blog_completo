<?php

namespace App\Http\Controllers\Publico;

use App\Enums\TipoPublicacion;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Publicacion;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function feed(): Response
    {
        $posts = Post::query()
            ->publicadas()
            ->with('categoria')
            ->latest('fecha_publicacion')
            ->limit(30)
            ->get();

        return response()
            ->view('publico.feed', ['posts' => $posts])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => route('home'), 'prioridad' => '1.0'],
            ['loc' => route('publico.sobre'), 'prioridad' => '0.5'],
            ['loc' => route('publico.autor'), 'prioridad' => '0.5'],
            ['loc' => route('publico.newsletter'), 'prioridad' => '0.5'],
        ];

        foreach (TipoPublicacion::cases() as $tipo) {
            $modelo = $tipo->modelo();

            foreach ($modelo::query()->publicadas()->latest('fecha_publicacion')->get() as $publicacion) {
                /** @var Publicacion $publicacion */
                $urls[] = [
                    'loc' => $publicacion->urlPublica(),
                    'lastmod' => $publicacion->updated_at?->toAtomString(),
                    'prioridad' => '0.8',
                ];
            }
        }

        return response()
            ->view('publico.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
