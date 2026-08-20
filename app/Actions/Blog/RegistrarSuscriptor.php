<?php

namespace App\Actions\Blog;

use App\Enums\EstadoSuscriptor;
use App\Mail\ConfirmacionSuscripcion;
use App\Models\Suscriptor;
use Illuminate\Support\Facades\Mail;

class RegistrarSuscriptor
{
    /**
     * Alta con doble opt-in. Devuelve el suscriptor y si ya estaba confirmado,
     * para que el llamador decida el mensaje sin repetir la lógica.
     *
     * @return array{suscriptor: Suscriptor, yaEstaba: bool}
     */
    public function __invoke(string $email, ?string $nombre, ?string $origen, ?string $ip): array
    {
        $suscriptor = Suscriptor::query()->firstOrNew(['email' => $email]);

        if ($suscriptor->exists && $suscriptor->estado === EstadoSuscriptor::Confirmado) {
            return ['suscriptor' => $suscriptor, 'yaEstaba' => true];
        }

        $suscriptor->fill([
            'nombre' => $nombre ?? $suscriptor->nombre,
            'estado' => EstadoSuscriptor::Pendiente,
            'origen' => $origen ?? 'sitio público',
            'ip_address' => $ip,
            'token' => Suscriptor::nuevoToken(),
            'baja_at' => null,
        ]);
        $suscriptor->save();

        Mail::to($suscriptor->email)->queue(new ConfirmacionSuscripcion($suscriptor));

        return ['suscriptor' => $suscriptor, 'yaEstaba' => false];
    }
}
