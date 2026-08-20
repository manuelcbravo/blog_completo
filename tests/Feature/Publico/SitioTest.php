<?php

use App\Enums\EstadoComentario;
use App\Enums\EstadoPublicacion;
use App\Enums\EstadoSuscriptor;
use App\Mail\ConfirmacionSuscripcion;
use App\Models\Categoria;
use App\Models\Comentario;
use App\Models\Post;
use App\Models\Suscriptor;
use App\Models\Tutorial;
use App\Models\Vista;
use Illuminate\Support\Facades\Mail;

function postPublicado(array $extra = []): Post
{
    return Post::query()->create(array_merge([
        'titulo' => 'Colas en Laravel',
        'resumen' => 'Backoff, jobs idempotentes y el día que un correo se envió cuatro mil veces.',
        'contenido' => '<p>El contenido del artículo.</p>',
        'tags_seo' => 'laravel, colas',
        'estado' => EstadoPublicacion::Publicado,
        'fecha_publicacion' => now()->subDay(),
    ], $extra));
}

test('la portada muestra lo publicado y esconde los borradores', function () {
    postPublicado();
    postPublicado(['titulo' => 'Sin terminar', 'estado' => EstadoPublicacion::Borrador]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Colas en Laravel')
        ->assertDontSee('Sin terminar');
});

test('la portada abre aunque no haya nada publicado', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Todavía no hay nada publicado');
});

test('el artículo se ve por su slug y registra la lectura', function () {
    $post = postPublicado();

    $this->get(route('publico.articulo', $post->slug))
        ->assertOk()
        ->assertSee('Colas en Laravel')
        ->assertSee('El contenido del artículo.', false);

    expect(Vista::query()->count())->toBe(1)
        ->and($post->fresh()->visitas)->toBe(1);
});

test('la misma sesión no infla el contador', function () {
    $post = postPublicado();

    $this->get(route('publico.articulo', $post->slug));
    $this->get(route('publico.articulo', $post->slug));

    expect(Vista::query()->count())->toBe(1)
        ->and($post->fresh()->visitas)->toBe(1);
});

test('un borrador no es accesible desde fuera', function () {
    $post = postPublicado(['estado' => EstadoPublicacion::Borrador]);

    $this->get(route('publico.articulo', $post->slug))->assertNotFound();
});

test('cada tipo tiene su listado', function () {
    postPublicado();
    Tutorial::query()->create([
        'titulo' => 'Instalar Postgres',
        'tags_seo' => 'postgres',
        'estado' => EstadoPublicacion::Publicado,
        'fecha_publicacion' => now(),
    ]);

    $this->get(route('publico.articulos'))->assertOk()->assertSee('Colas en Laravel');
    $this->get(route('publico.tutoriales'))->assertOk()->assertSee('Instalar Postgres');
    $this->get(route('publico.recursos'))->assertOk();
});

test('la categoría lista sus artículos', function () {
    $categoria = Categoria::query()->create(['nombre' => 'Colas', 'slug' => 'colas']);
    postPublicado(['id_categoria' => $categoria->id]);

    $this->get(route('publico.categoria', 'colas'))
        ->assertOk()
        ->assertSee('Colas en Laravel');
});

test('la búsqueda encuentra por título', function () {
    postPublicado();

    $this->get(route('publico.buscar', ['q' => 'colas']))
        ->assertOk()
        ->assertSee('Colas en Laravel');

    $this->get(route('publico.buscar', ['q' => 'nomatch']))
        ->assertOk()
        ->assertSee('Sin resultados');
});

test('un visitante puede comentar desde el artículo', function () {
    Mail::fake();
    config()->set('blog.comentarios_moderacion', true);

    $post = postPublicado();

    $this->post(route('publico.comentar'), [
        'tipo' => 'post',
        'post_id' => $post->id,
        'nombre' => 'Visitante',
        'correo' => 'visitante@example.com',
        'contenido' => 'Justo lo que necesitaba, gracias.',
    ])->assertRedirect();

    $comentario = Comentario::query()->firstOrFail();

    expect($comentario->estado)->toBe(EstadoComentario::Pendiente)
        ->and($comentario->post_id)->toBe($post->id);
});

test('el comentario pendiente no se muestra y el aprobado sí', function () {
    $post = postPublicado();

    Comentario::query()->create([
        'post_id' => $post->id,
        'tipo' => 'post',
        'nombre' => 'Aprobado',
        'correo' => 'a@example.com',
        'contenido' => 'Comentario visible',
        'estado' => EstadoComentario::Aprobado,
    ]);

    Comentario::query()->create([
        'post_id' => $post->id,
        'tipo' => 'post',
        'nombre' => 'Pendiente',
        'correo' => 'p@example.com',
        'contenido' => 'Comentario oculto',
        'estado' => EstadoComentario::Pendiente,
    ]);

    $this->get(route('publico.articulo', $post->slug))
        ->assertSee('Comentario visible')
        ->assertDontSee('Comentario oculto');
});

test('el formulario de newsletter da de alta con doble opt-in', function () {
    Mail::fake();

    $this->post(route('publico.suscribir'), [
        'email' => 'lector@example.com',
        'origen' => 'página newsletter',
    ])->assertRedirect();

    expect(Suscriptor::query()->firstOrFail()->estado)->toBe(EstadoSuscriptor::Pendiente);

    Mail::assertQueued(ConfirmacionSuscripcion::class);
});

test('el honeypot corta el alta desde el sitio', function () {
    Mail::fake();

    $this->post(route('publico.suscribir'), [
        'email' => 'bot@example.com',
        'sitio_web' => 'http://spam.example',
    ])->assertSessionHasErrors('sitio_web');

    expect(Suscriptor::query()->count())->toBe(0);
});

test('el feed y el sitemap responden xml', function () {
    postPublicado();

    $this->get(route('feed'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
        ->assertSee('Colas en Laravel');

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('publico.articulo', 'colas-en-laravel'));
});

test('las páginas fijas abren', function (string $ruta) {
    $this->get(route($ruta))->assertOk();
})->with(['publico.sobre', 'publico.autor', 'publico.newsletter', 'publico.buscar']);
