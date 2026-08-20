<?php

namespace App\Http\Controllers\Publico;

use App\Actions\Blog\RegistrarSuscriptor;
use App\Enums\EstadoSuscriptor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publico\SuscribirRequest;
use App\Mail\BienvenidaSuscriptor;
use App\Models\Suscriptor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class SuscripcionController extends Controller
{
    public function store(SuscribirRequest $request, RegistrarSuscriptor $registrar): JsonResponse
    {
        $datos = $request->validated();

        $resultado = $registrar(
            $datos['email'],
            $datos['nombre'] ?? null,
            $datos['origen'] ?? null,
            $request->ip(),
        );

        if ($resultado['yaEstaba']) {
            return response()->json([
                'mensaje' => 'Ese correo ya está suscrito.',
                'estado' => $resultado['suscriptor']->estado->value,
            ]);
        }

        return response()->json([
            'mensaje' => 'Te envié un correo para confirmar tu suscripción.',
            'estado' => $resultado['suscriptor']->estado->value,
        ], 201);
    }

    public function confirmar(string $token): View
    {
        $suscriptor = Suscriptor::query()->where('token', $token)->first();

        if ($suscriptor === null) {
            return view('suscripcion', [
                'titulo' => 'Enlace no válido',
                'mensaje' => 'El enlace de confirmación ya no existe o fue reemplazado por uno más reciente.',
            ]);
        }

        if ($suscriptor->estado !== EstadoSuscriptor::Confirmado) {
            $suscriptor->confirmar();
            Mail::to($suscriptor->email)->queue(new BienvenidaSuscriptor($suscriptor));
        }

        return view('suscripcion', [
            'titulo' => 'Suscripción confirmada',
            'mensaje' => 'Listo, ya te aviso cuando publique algo nuevo.',
        ]);
    }

    public function baja(string $token): View
    {
        $suscriptor = Suscriptor::query()->where('token', $token)->first();

        if ($suscriptor === null) {
            return view('suscripcion', [
                'titulo' => 'Enlace no válido',
                'mensaje' => 'No encontramos esa suscripción.',
            ]);
        }

        $suscriptor->darDeBaja();

        return view('suscripcion', [
            'titulo' => 'Suscripción cancelada',
            'mensaje' => 'Ya no vas a recibir más correos. Puedes volver a suscribirte cuando quieras.',
        ]);
    }
}
