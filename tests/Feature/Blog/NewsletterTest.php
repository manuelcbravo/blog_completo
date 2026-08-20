<?php

use App\Enums\EstadoPublicacion;
use App\Enums\EstadoSuscriptor;
use App\Enums\TipoPublicacion;
use App\Jobs\EnviarNewsletter;
use App\Mail\NuevaPublicacion;
use App\Models\Post;
use App\Models\Suscriptor;
use App\Models\Vista;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

test('publicar dispara el job del newsletter', function () {
    Bus::fake();

    $post = Post::query()->create([
        'titulo' => 'Borrador',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
    ]);

    Bus::assertNotDispatched(EnviarNewsletter::class);

    $post->estado = EstadoPublicacion::Publicado;
    $post->save();

    Bus::assertDispatched(EnviarNewsletter::class);
});

test('editar sin cambiar el estado no vuelve a disparar el newsletter', function () {
    $post = Post::query()->create([
        'titulo' => 'Publicado',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Publicado,
    ]);

    Bus::fake();

    $post->resumen = 'Otro resumen';
    $post->save();

    Bus::assertNotDispatched(EnviarNewsletter::class);
});

test('el job solo escribe a los suscriptores confirmados', function () {
    Mail::fake();

    $post = Post::query()->create([
        'titulo' => 'Publicado',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Publicado,
    ]);

    Suscriptor::query()->create([
        'email' => 'confirmado@example.com',
        'estado' => EstadoSuscriptor::Confirmado,
        'token' => Suscriptor::nuevoToken(),
    ]);

    Suscriptor::query()->create([
        'email' => 'pendiente@example.com',
        'estado' => EstadoSuscriptor::Pendiente,
        'token' => Suscriptor::nuevoToken(),
    ]);

    (new EnviarNewsletter(TipoPublicacion::Post, $post->id))->handle();

    Mail::assertQueued(NuevaPublicacion::class, 1);
});

test('el comando publica lo programado cuando llega la fecha', function () {
    $listo = Post::query()->create([
        'titulo' => 'Programado para ayer',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Programado,
        'fecha_publicacion' => now()->subHour(),
    ]);

    $futuro = Post::query()->create([
        'titulo' => 'Programado para mañana',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Programado,
        'fecha_publicacion' => now()->addDay(),
    ]);

    $this->artisan('blog:publicar-programadas')->assertSuccessful();

    expect($listo->fresh()->estado)->toBe(EstadoPublicacion::Publicado)
        ->and($futuro->fresh()->estado)->toBe(EstadoPublicacion::Programado);
});

test('el registro de vista suma la visita', function () {
    $post = Post::query()->create([
        'titulo' => 'Publicado',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Publicado,
    ]);

    $this->postJson(route('api.blog.vistas.store'), [
        'tipo' => 'post',
        'post_id' => $post->id,
        'session_id' => 'abc123',
    ])->assertCreated();

    expect(Vista::query()->count())->toBe(1)
        ->and($post->fresh()->visitas)->toBe(1);
});
