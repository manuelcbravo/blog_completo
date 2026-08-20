<?php

namespace App\Http\Controllers\Publico;

use App\Actions\Blog\RegistrarComentario;
use App\Actions\Blog\RegistrarContacto;
use App\Enums\TipoPublicacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publico\ComentarRequest;
use App\Http\Requests\Publico\ContactarRequest;
use App\Http\Requests\Publico\RegistrarVistaRequest;
use App\Models\Vista;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InteraccionController extends Controller
{
    public function comentar(ComentarRequest $request, RegistrarComentario $registrar): JsonResponse
    {
        $datos = $request->validated();
        $tipo = TipoPublicacion::from($datos['tipo']);
        $modelo = $tipo->modelo();
        $publicacion = $modelo::query()->findOrFail((int) $datos['post_id']);

        $comentario = $registrar($publicacion, [
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'contenido' => $datos['contenido'],
            'parent_id' => $datos['parent_id'] ?? null,
        ], $request->ip(), $request->userAgent());

        return response()->json([
            'mensaje' => config('blog.comentarios_moderacion')
                ? 'Gracias. Publico tu comentario en cuanto lo revise.'
                : 'Gracias por comentar.',
            'estado' => $comentario->estado->value,
        ], 201);
    }

    public function contactar(ContactarRequest $request, RegistrarContacto $registrar): JsonResponse
    {
        $datos = $request->validated();

        $registrar([
            'nombre' => (string) $datos['nombre'],
            'email' => (string) $datos['email'],
            'mensaje' => (string) $datos['mensaje'],
        ], $request->ip(), $request->userAgent());

        return response()->json([
            'mensaje' => 'Gracias por escribir, te respondo en breve.',
        ], 201);
    }

    public function registrarVista(RegistrarVistaRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $tipo = TipoPublicacion::from($datos['tipo']);
        $modelo = $tipo->modelo();
        $publicacion = $modelo::query()->findOrFail((int) $datos['post_id']);

        Vista::query()->create([
            'post_id' => $publicacion->id,
            'tipo' => $tipo->value,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'referer' => $datos['referer'] ?? $request->header('referer'),
            'session_id' => $datos['session_id'] ?? null,
        ]);

        $publicacion->increment('visitas');

        return response()->json(['registrada' => true], 201);
    }

    public function salud(Request $request): JsonResponse
    {
        return response()->json(['ok' => true]);
    }
}
