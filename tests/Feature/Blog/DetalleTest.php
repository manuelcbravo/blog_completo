<?php

use App\Enums\EstadoPublicacion;
use App\Enums\Permiso;
use App\Models\Recurso;
use App\Models\RecursoDetalle;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

function usuarioGestorDeRecursos(): User
{
    Permission::findOrCreate(Permiso::BlogPublicacionesGestionar->value);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(Permiso::BlogPublicacionesGestionar->value);

    return $usuario;
}

function recursoDePrueba(User $autor): Recurso
{
    return Recurso::query()->create([
        'titulo' => 'Recurso de prueba',
        'estado' => EstadoPublicacion::Borrador,
        'id_autor' => $autor->id,
    ]);
}

test('se adjunta un archivo de un formato permitido', function () {
    Storage::fake(config('blog.disco'));

    $usuario = usuarioGestorDeRecursos();
    $this->actingAs($usuario);

    $recurso = recursoDePrueba($usuario);

    $this->post(route('blog.detalles.store', ['recurso' => $recurso->id]), [
        'detalle' => 'Guía en PDF',
        'archivo' => UploadedFile::fake()->create('guia.pdf', 120, 'application/pdf'),
    ])->assertRedirect();

    $detalle = RecursoDetalle::query()->firstOrFail();

    expect($detalle->nombre_original)->toBe('guia.pdf')
        ->and($detalle->id_recurso)->toBe($recurso->id);

    Storage::disk(config('blog.disco'))->assertExists($detalle->recurso_url);
});

/*
 * Los archivos se sirven con su URL directa del disco público, así que un HTML
 * subido aquí se abriría como página en el dominio de archivos. La lista blanca
 * del Form Request es lo que lo impide; esta prueba la fija.
 */
test('no se adjunta un archivo que el navegador ejecutaría', function () {
    Storage::fake(config('blog.disco'));

    $usuario = usuarioGestorDeRecursos();
    $this->actingAs($usuario);

    $recurso = recursoDePrueba($usuario);

    $this->post(route('blog.detalles.store', ['recurso' => $recurso->id]), [
        'detalle' => 'Intento de HTML',
        'archivo' => UploadedFile::fake()->createWithContent(
            'trampa.html',
            '<script>alert(document.domain)</script>',
        ),
    ])->assertSessionHasErrors('archivo');

    expect(RecursoDetalle::query()->count())->toBe(0);

    Storage::disk(config('blog.disco'))->assertDirectoryEmpty('recursos/archivos');
});

test('un usuario sin permiso no adjunta archivos', function () {
    $this->actingAs(User::factory()->create());

    $recurso = recursoDePrueba(User::factory()->create());

    $this->post(route('blog.detalles.store', ['recurso' => $recurso->id]), [
        'detalle' => 'Sin permiso',
        'archivo' => UploadedFile::fake()->create('guia.pdf', 10, 'application/pdf'),
    ])->assertForbidden();
});
