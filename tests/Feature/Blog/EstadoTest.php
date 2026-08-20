<?php

use App\Enums\EstadoPublicacion;
use App\Enums\Permiso;
use App\Jobs\EnviarNewsletter;
use App\Models\Post;
use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Permission;

function editorDePublicaciones(): User
{
    foreach ([Permiso::BlogPublicacionesVer, Permiso::BlogPublicacionesGestionar, Permiso::BlogPublicacionesPublicar] as $permiso) {
        Permission::findOrCreate($permiso->value);
    }

    $usuario = User::factory()->create();
    $usuario->givePermissionTo([
        Permiso::BlogPublicacionesVer->value,
        Permiso::BlogPublicacionesGestionar->value,
        Permiso::BlogPublicacionesPublicar->value,
    ]);

    return $usuario;
}

test('el cambio rapido de estatus publica y encola el aviso', function () {
    Bus::fake();
    $this->actingAs(editorDePublicaciones());

    $post = Post::query()->create([
        'titulo' => 'Listo para salir',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
    ]);

    $this->post(
        route('blog.publicaciones.estado.store', ['tipo' => 'posts', 'publicacion' => $post->id]),
        ['estado' => EstadoPublicacion::Publicado->value],
    )->assertRedirect();

    $post->refresh();

    expect($post->estado)->toBe(EstadoPublicacion::Publicado)
        ->and($post->fecha_publicacion)->not->toBeNull();

    Bus::assertDispatched(EnviarNewsletter::class);
});

test('bajar de publicado no encola nada', function () {
    $tutorial = Tutorial::query()->create([
        'titulo' => 'Ya publicado',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Publicado,
    ]);

    Bus::fake();
    $this->actingAs(editorDePublicaciones());

    $this->post(
        route('blog.publicaciones.estado.store', ['tipo' => 'tutoriales', 'publicacion' => $tutorial->id]),
        ['estado' => EstadoPublicacion::Abajo->value],
    )->assertRedirect();

    expect($tutorial->fresh()->estado)->toBe(EstadoPublicacion::Abajo);

    Bus::assertNotDispatched(EnviarNewsletter::class);
});

test('programar sin fecha no pasa la validacion', function () {
    $this->actingAs(editorDePublicaciones());

    $post = Post::query()->create([
        'titulo' => 'Sin fecha',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
    ]);

    $this->post(
        route('blog.publicaciones.estado.store', ['tipo' => 'posts', 'publicacion' => $post->id]),
        ['estado' => EstadoPublicacion::Programado->value],
    )->assertSessionHasErrors('fecha_publicacion');

    expect($post->fresh()->estado)->toBe(EstadoPublicacion::Borrador);
});

test('programar con fecha guarda el estado y la fecha', function () {
    $this->actingAs(editorDePublicaciones());

    $post = Post::query()->create([
        'titulo' => 'Con fecha',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
    ]);

    $fecha = now()->addDays(3)->startOfMinute();

    $this->post(
        route('blog.publicaciones.estado.store', ['tipo' => 'posts', 'publicacion' => $post->id]),
        [
            'estado' => EstadoPublicacion::Programado->value,
            'fecha_publicacion' => $fecha->toDateTimeString(),
        ],
    )->assertRedirect();

    $post->refresh();

    expect($post->estado)->toBe(EstadoPublicacion::Programado)
        ->and($post->fecha_publicacion?->toDateTimeString())->toBe($fecha->toDateTimeString());
});

test('sin permiso no se puede cambiar el estatus', function () {
    $this->actingAs(User::factory()->create());

    $post = Post::query()->create([
        'titulo' => 'Protegido',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
    ]);

    $this->post(
        route('blog.publicaciones.estado.store', ['tipo' => 'posts', 'publicacion' => $post->id]),
        ['estado' => EstadoPublicacion::Publicado->value],
    )->assertForbidden();

    expect($post->fresh()->estado)->toBe(EstadoPublicacion::Borrador);
});
