<?php

use App\Enums\EstadoPublicacion;
use App\Enums\Permiso;
use App\Jobs\EnviarNewsletter;
use App\Models\Post;
use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Permission;

function autorDeProgramadas(): User
{
    // Programar es publicar con retraso, así que pide el permiso de publicar.
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

test('la app corre en la zona horaria configurada', function () {
    expect(config('app.timezone'))->toBe(config('app.timezone'))
        ->and(now()->timezone->getName())->toBe(config('app.timezone'));
});

test('el comando publica lo vencido y respeta lo futuro', function () {
    Bus::fake();

    $vencida = Post::query()->create([
        'titulo' => 'Salía hace una hora',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Programado,
        'fecha_publicacion' => now()->subHour(),
    ]);

    $futura = Tutorial::query()->create([
        'titulo' => 'Sale mañana',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Programado,
        'fecha_publicacion' => now()->addDay(),
    ]);

    $this->artisan('blog:publicar-programadas')
        ->expectsOutputToContain('Publicada: Salía hace una hora')
        ->assertSuccessful();

    expect($vencida->fresh()->estado)->toBe(EstadoPublicacion::Publicado)
        ->and($futura->fresh()->estado)->toBe(EstadoPublicacion::Programado);

    Bus::assertDispatchedTimes(EnviarNewsletter::class, 1);
});

test('la opcion --pendientes no publica nada', function () {
    $vencida = Post::query()->create([
        'titulo' => 'Ya toca',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Programado,
        'fecha_publicacion' => now()->subMinute(),
    ]);

    $this->artisan('blog:publicar-programadas --pendientes')->assertSuccessful();

    expect($vencida->fresh()->estado)->toBe(EstadoPublicacion::Programado);
});

test('el comando avisa de las programadas sin fecha', function () {
    Post::query()->create([
        'titulo' => 'Atorada',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Programado,
        'fecha_publicacion' => null,
    ]);

    $this->artisan('blog:publicar-programadas')
        ->expectsOutputToContain('Sin fecha, nunca saldra: Atorada')
        ->assertSuccessful();
});

test('el formulario no deja programar sin fecha', function () {
    $usuario = autorDeProgramadas();
    $this->actingAs($usuario);

    $this->post(route('blog.publicaciones.store', ['tipo' => 'posts']), [
        'titulo' => 'Sin fecha',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Programado->value,
        'id_autor' => $usuario->id,
    ])->assertSessionHasErrors('fecha_publicacion');

    expect(Post::query()->count())->toBe(0);
});

test('el formulario guarda la publicacion programada con su fecha', function () {
    $usuario = autorDeProgramadas();
    $this->actingAs($usuario);

    $fecha = now()->addDays(2)->startOfMinute();

    $this->post(route('blog.publicaciones.store', ['tipo' => 'posts']), [
        'titulo' => 'Sale el jueves',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Programado->value,
        'fecha_publicacion' => $fecha->toDateTimeString(),
        'id_autor' => $usuario->id,
    ])->assertRedirect();

    $post = Post::query()->firstOrFail();

    expect($post->estado)->toBe(EstadoPublicacion::Programado)
        ->and($post->fecha_publicacion?->toDateTimeString())->toBe($fecha->toDateTimeString());
});

test('el dashboard lista lo que está en cola de publicación', function () {
    $usuario = User::factory()->create();
    $usuario->forceFill(['es_super_admin' => true])->save();
    $this->actingAs($usuario);

    Post::query()->create([
        'titulo' => 'En cola',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Programado,
        'fecha_publicacion' => now()->addDay(),
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('dashboard')
            ->where('analitica.resumen.programadas', 1)
            ->has('analitica.programadas', 1)
            ->where('analitica.programadas.0.titulo', 'En cola')
            ->where('analitica.programadas.0.sinFecha', false));
});
