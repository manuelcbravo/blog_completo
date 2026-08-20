<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\UpsertDetalleRequest;
use App\Models\Recurso;
use App\Models\RecursoDetalle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class DetalleController extends Controller
{
    public function store(UpsertDetalleRequest $request, int $recurso): RedirectResponse
    {
        $registro = Recurso::query()->findOrFail($recurso);
        $datos = $request->validated();

        $ruta = $request->file('archivo')->storePublicly('recursos/archivos', config('blog.disco'));

        $registro->detalles()->create([
            'detalle' => $datos['detalle'],
            'recurso_url' => $ruta,
            'nombre_original' => $request->file('archivo')->getClientOriginalName(),
            'tamano' => $request->file('archivo')->getSize(),
            'orden' => $datos['orden'] ?? ($registro->detalles()->max('orden') + 1),
        ]);

        return back()->with('success', 'Archivo agregado al recurso.');
    }

    public function destroy(int $recurso, RecursoDetalle $detalle): RedirectResponse
    {
        abort_unless($detalle->id_recurso === $recurso, 404);

        if ($detalle->recurso_url !== null) {
            Storage::disk(config('blog.disco'))->delete($detalle->recurso_url);
        }

        $detalle->delete();

        return back()->with('success', 'Archivo eliminado del recurso.');
    }
}
