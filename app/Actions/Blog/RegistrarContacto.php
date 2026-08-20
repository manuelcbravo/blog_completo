<?php

namespace App\Actions\Blog;

use App\Mail\AcuseContacto;
use App\Mail\NuevoContacto;
use App\Models\Contacto;
use Illuminate\Support\Facades\Mail;

class RegistrarContacto
{
    /**
     * @param  array{nombre: string, email: string, mensaje: string}  $datos
     */
    public function __invoke(array $datos, ?string $ip, ?string $userAgent): Contacto
    {
        $contacto = Contacto::query()->create([
            'name' => $datos['nombre'],
            'email' => $datos['email'],
            'message' => $datos['mensaje'],
            'ip_address' => $ip,
            'user_agent' => $userAgent === null ? null : substr($userAgent, 0, 500),
        ]);

        $destinatario = config('blog.admin_email');

        if (is_string($destinatario) && $destinatario !== '') {
            Mail::to($destinatario)->queue(new NuevoContacto($contacto));
        }

        Mail::to($contacto->email)->queue(new AcuseContacto($contacto));

        return $contacto;
    }
}
