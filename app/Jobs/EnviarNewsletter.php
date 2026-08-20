<?php

namespace App\Jobs;

use App\Enums\EstadoPublicacion;
use App\Enums\TipoPublicacion;
use App\Mail\NuevaPublicacion;
use App\Models\Suscriptor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class EnviarNewsletter implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TipoPublicacion $tipo,
        public int $publicacionId,
    ) {}

    public function handle(): void
    {
        if (! config('blog.newsletter_activo')) {
            return;
        }

        $modelo = $this->tipo->modelo();
        $publicacion = $modelo::query()->find($this->publicacionId);

        if ($publicacion === null || $publicacion->estado !== EstadoPublicacion::Publicado) {
            return;
        }

        Suscriptor::query()
            ->confirmados()
            ->chunkById((int) config('blog.newsletter_lote'), function (Collection $suscriptores) use ($publicacion): void {
                foreach ($suscriptores as $suscriptor) {
                    Mail::to($suscriptor->email)->queue(new NuevaPublicacion($publicacion, $suscriptor));
                }
            });
    }
}
