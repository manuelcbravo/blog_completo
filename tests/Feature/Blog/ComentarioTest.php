<?php

use App\Enums\EstadoComentario;
use App\Enums\EstadoPublicacion;
use App\Enums\Permiso;
use App\Mail\NuevoComentario;
use App\Mail\RespuestaComentario;
use App\Models\Comentario;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;

function moderador(): User
{
    Permission::findOrCreate(Permiso::BlogComentariosVer->value);
    Permission::findOrCreate(Permiso::BlogComentariosModerar->value);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo([
        Permiso::BlogComentariosVer->value,
        Permiso::BlogComentariosModerar->value,
    ]);

    return $usuario;
}

function postDePrueba(): Post
{
    return Post::query()->create([
        'titulo' => 'Post comentado',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
    ]);
}

test('un comentario publico entra pendiente y avisa al admin', function () {
    Mail::fake();
    config()->set('blog.comentarios_moderacion', true);
    config()->set('blog.admin_email', 'admin@example.com');

    $post = postDePrueba();

    $this->postJson(route('api.blog.comentarios.store'), [
        'tipo' => 'post',
        'post_id' => $post->id,
        'nombre' => 'Visitante',
        'correo' => 'visitante@example.com',
        'contenido' => 'Muy buen artículo, gracias.',
    ])->assertCreated();

    $comentario = Comentario::query()->firstOrFail();

    expect($comentario->estado)->toBe(EstadoComentario::Pendiente)
        ->and($comentario->ip_address)->not->toBeNull();

    Mail::assertQueued(NuevoComentario::class);
});

test('sin moderacion el comentario entra aprobado', function () {
    Mail::fake();
    config()->set('blog.comentarios_moderacion', false);

    $post = postDePrueba();

    $this->postJson(route('api.blog.comentarios.store'), [
        'tipo' => 'post',
        'post_id' => $post->id,
        'nombre' => 'Visitante',
        'correo' => 'visitante@example.com',
        'contenido' => 'Comentario directo.',
    ])->assertCreated();

    expect(Comentario::query()->firstOrFail()->estado)->toBe(EstadoComentario::Aprobado);
});

test('el honeypot bloquea el comentario', function () {
    $post = postDePrueba();

    $this->postJson(route('api.blog.comentarios.store'), [
        'tipo' => 'post',
        'post_id' => $post->id,
        'nombre' => 'Bot',
        'correo' => 'bot@example.com',
        'contenido' => 'spam spam spam',
        'sitio_web' => 'http://spam.example',
    ])->assertStatus(422);

    expect(Comentario::query()->count())->toBe(0);
});

test('el moderador aprueba un comentario', function () {
    $this->actingAs(moderador());
    $post = postDePrueba();

    $comentario = Comentario::query()->create([
        'post_id' => $post->id,
        'tipo' => 'post',
        'nombre' => 'Visitante',
        'correo' => 'visitante@example.com',
        'contenido' => 'Pendiente',
        'estado' => EstadoComentario::Pendiente,
    ]);

    $this->post(route('blog.comentarios.store'), [
        'id' => $comentario->id,
        'estado' => EstadoComentario::Aprobado->value,
    ])->assertRedirect();

    expect($comentario->fresh()->estado)->toBe(EstadoComentario::Aprobado)
        ->and($comentario->fresh()->moderado_at)->not->toBeNull();
});

test('responder crea el comentario hijo y notifica al autor', function () {
    Mail::fake();
    $this->actingAs(moderador());
    $post = postDePrueba();

    $comentario = Comentario::query()->create([
        'post_id' => $post->id,
        'tipo' => 'post',
        'nombre' => 'Visitante',
        'correo' => 'visitante@example.com',
        'contenido' => '¿Tienes el repositorio?',
        'estado' => EstadoComentario::Pendiente,
    ]);

    $this->post(route('blog.comentarios.store'), [
        'id' => $comentario->id,
        'estado' => EstadoComentario::Aprobado->value,
        'respuesta' => 'Sí, lo publico esta semana.',
        'notificar' => true,
    ])->assertRedirect();

    $respuesta = Comentario::query()->where('parent_id', $comentario->id)->firstOrFail();

    expect($respuesta->estado)->toBe(EstadoComentario::Aprobado)
        ->and($respuesta->contenido)->toBe('Sí, lo publico esta semana.');

    Mail::assertQueued(RespuestaComentario::class);
});

test('el listado solo muestra comentarios raiz', function () {
    $this->actingAs(moderador());
    $post = postDePrueba();

    $padre = Comentario::query()->create([
        'post_id' => $post->id,
        'tipo' => 'post',
        'nombre' => 'Visitante',
        'correo' => 'visitante@example.com',
        'contenido' => 'Pregunta',
        'estado' => EstadoComentario::Aprobado,
    ]);

    Comentario::query()->create([
        'post_id' => $post->id,
        'tipo' => 'post',
        'parent_id' => $padre->id,
        'nombre' => 'Equipo',
        'correo' => 'equipo@example.com',
        'contenido' => 'Respuesta',
        'estado' => EstadoComentario::Aprobado,
    ]);

    $this->get(route('blog.comentarios.index'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('blog/comentarios/index')
            ->has('comentarios', 1)
            ->where('comentarios.0.publicacion_titulo', 'Post comentado')
            ->has('comentarios.0.respuestas', 1));
});
