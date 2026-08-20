<?php

namespace App\Actions\Blog;

use App\Enums\EstadoComentario;
use App\Mail\NuevoComentario;
use App\Models\Comentario;
use App\Models\Publicacion;
use Illuminate\Support\Facades\Mail;

class RegistrarComentario
{
    /**
     * @param  array{nombre: string, correo: string, contenido: string, parent_id?: int|null}  $datos
     */
    public function __invoke(Publicacion $publicacion, array $datos, ?string $ip, ?string $userAgent): Comentario
    {
        $moderacion = (bool) config('blog.comentarios_moderacion');

        $comentario = Comentario::query()->create([
            'post_id' => $publicacion->id,
            'tipo' => $publicacion->tipo()->value,
            'parent_id' => $datos['parent_id'] ?? null,
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'contenido' => $datos['contenido'],
            'estado' => $moderacion ? EstadoComentario::Pendiente : EstadoComentario::Aprobado,
            'ip_address' => $ip,
            'user_agent' => $userAgent === null ? null : substr($userAgent, 0, 500),
            'moderado_at' => $moderacion ? null : now(),
        ]);

        $destinatario = config('blog.admin_email');

        if (is_string($destinatario) && $destinatario !== '') {
            Mail::to($destinatario)->queue(new NuevoComentario($comentario, $publicacion->titulo));
        }

        return $comentario;
    }
}
