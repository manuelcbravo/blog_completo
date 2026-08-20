<?php

namespace App\Observers;

use App\Enums\EstadoPublicacion;
use App\Jobs\EnviarNewsletter;
use App\Models\Publicacion;
use Illuminate\Support\Str;

class PublicacionObserver
{
    public function saving(Publicacion $publicacion): void
    {
        if (blank($publicacion->slug)) {
            $publicacion->slug = $this->slugUnico($publicacion, Str::slug($publicacion->titulo));
        }

        $publicacion->tiempo_lectura = $this->tiempoLectura($publicacion->contenido);

        if ($publicacion->estado === EstadoPublicacion::Publicado && $publicacion->fecha_publicacion === null) {
            $publicacion->fecha_publicacion = now();
        }
    }

    public function created(Publicacion $publicacion): void
    {
        $this->avisarSiSePublico($publicacion);
    }

    public function updated(Publicacion $publicacion): void
    {
        if (! $publicacion->wasChanged('estado')) {
            return;
        }

        $this->avisarSiSePublico($publicacion);
    }

    private function avisarSiSePublico(Publicacion $publicacion): void
    {
        if ($publicacion->estado !== EstadoPublicacion::Publicado) {
            return;
        }

        EnviarNewsletter::dispatch($publicacion->tipo(), $publicacion->id);
    }

    private function slugUnico(Publicacion $publicacion, string $base): string
    {
        $base = $base === '' ? 'publicacion' : $base;
        $slug = $base;
        $sufijo = 1;

        while ($this->slugOcupado($publicacion, $slug)) {
            $sufijo++;
            $slug = "{$base}-{$sufijo}";
        }

        return $slug;
    }

    private function slugOcupado(Publicacion $publicacion, string $slug): bool
    {
        return $publicacion->newQuery()
            ->withTrashed()
            ->where('slug', $slug)
            ->when($publicacion->exists, fn ($query) => $query->whereKeyNot($publicacion->getKey()))
            ->exists();
    }

    private function tiempoLectura(?string $contenido): int
    {
        $texto = trim(strip_tags((string) $contenido));

        if ($texto === '') {
            return 1;
        }

        $palabras = str_word_count($texto);
        $porMinuto = max(1, (int) config('blog.palabras_por_minuto'));

        return max(1, (int) ceil($palabras / $porMinuto));
    }
}
