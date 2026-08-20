<?php

use App\Enums\EstadoPublicacion;
use App\Enums\Permiso;
use App\Enums\Rol;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UsuarioDemoSeeder;

function usuarioDemo(): User
{
    (new RoleSeeder)->run();

    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Demo->value);

    return $usuario;
}

function publicacionDePrueba(User $autor, EstadoPublicacion $estado = EstadoPublicacion::Borrador): Post
{
    return Post::query()->create([
        'slug' => 'publicacion-de-prueba',
        'titulo' => 'Publicación de prueba',
        'estado' => $estado,
        'id_autor' => $autor->id,
    ]);
}

test('el rol demo consulta el listado y el contenido', function () {
    $demo = usuarioDemo();
    $publicacion = publicacionDePrueba($demo);

    $this->actingAs($demo);

    $this->get(route('blog.publicaciones.index', ['tipo' => 'posts']))->assertOk();
    $this->get(route('blog.publicaciones.contenido.index', [
        'tipo' => 'posts',
        'publicacion' => $publicacion->id,
    ]))->assertOk();

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('blog.categorias.index'))->assertOk();
    $this->get(route('blog.comentarios.index'))->assertOk();
});

test('el rol demo puede crear una publicación', function () {
    $demo = usuarioDemo();
    $this->actingAs($demo);

    $this->post(route('blog.publicaciones.store', ['tipo' => 'posts']), [
        'titulo' => 'Escrita desde la demostración',
        'resumen' => 'Una prueba.',
        'tags_seo' => 'laravel, demo',
        'estado' => EstadoPublicacion::Borrador->value,
        'id_autor' => $demo->id,
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect(Post::query()->where('titulo', 'Escrita desde la demostración')->exists())->toBeTrue();
});

/*
 * El upsert recibe el estado como un campo del formulario, así que sin guardia
 * el demo publicaría sin pasar por la ruta de estado, que sí está protegida.
 */
test('el rol demo no publica mandando el estado en el formulario', function () {
    $demo = usuarioDemo();
    $this->actingAs($demo);

    $this->post(route('blog.publicaciones.store', ['tipo' => 'posts']), [
        'titulo' => 'Intento de publicar por la puerta de atrás',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Publicado->value,
        'id_autor' => $demo->id,
    ])->assertSessionHasErrors('estado');

    expect(Post::query()->count())->toBe(0);
});

test('el rol demo tampoco retira del sitio algo ya publicado', function () {
    $demo = usuarioDemo();
    $publicacion = publicacionDePrueba($demo, EstadoPublicacion::Publicado);

    $this->actingAs($demo);

    $this->post(route('blog.publicaciones.store', ['tipo' => 'posts']), [
        'id' => $publicacion->id,
        'titulo' => $publicacion->titulo,
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Abajo->value,
        'id_autor' => $demo->id,
    ])->assertSessionHasErrors('estado');

    expect($publicacion->fresh()->estado)->toBe(EstadoPublicacion::Publicado);
});

test('el rol demo sí puede editar sin cambiarle el estado', function () {
    $demo = usuarioDemo();
    $publicacion = publicacionDePrueba($demo, EstadoPublicacion::Publicado);

    $this->actingAs($demo);

    $this->post(route('blog.publicaciones.store', ['tipo' => 'posts']), [
        'id' => $publicacion->id,
        'titulo' => 'Título corregido por el demo',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Publicado->value,
        'id_autor' => $demo->id,
    ])->assertSessionHasNoErrors();

    expect($publicacion->fresh()->titulo)->toBe('Título corregido por el demo');
});

test('el rol demo no puede publicar', function () {
    $demo = usuarioDemo();
    $publicacion = publicacionDePrueba($demo);

    $this->actingAs($demo);

    $this->post(route('blog.publicaciones.estado.store', [
        'tipo' => 'posts',
        'publicacion' => $publicacion->id,
    ]), ['estado' => EstadoPublicacion::Publicado->value])->assertForbidden();

    expect($publicacion->fresh()->estado)->toBe(EstadoPublicacion::Borrador);
});

test('el rol demo no puede eliminar', function () {
    $demo = usuarioDemo();
    $publicacion = publicacionDePrueba($demo);

    $this->actingAs($demo);

    $this->delete(route('blog.publicaciones.destroy', [
        'tipo' => 'posts',
        'publicacion' => $publicacion->id,
    ]))->assertForbidden();

    expect(Post::query()->whereKey($publicacion->id)->exists())->toBeTrue();
});

/*
 * La credencial de la cuenta demo está pensada para publicarse, así que quien
 * entre no debe poder leer correos de suscriptores, mensajes de contacto ni las
 * IP de la bitácora de visitas.
 */
test('el rol demo no llega a ningún dato personal de terceros', function () {
    $demo = usuarioDemo();
    $this->actingAs($demo);

    foreach (Permiso::conDatosPersonales() as $permiso) {
        expect($demo->can($permiso->value))->toBeFalse();
    }

    $this->get(route('blog.suscriptores.index'))->assertForbidden();
    $this->get(route('blog.contactos.index'))->assertForbidden();
    $this->get(route('blog.visitas.index'))->assertForbidden();
});

test('el rol demo no toca taxonomías, moderación ni usuarios', function () {
    $demo = usuarioDemo();
    $this->actingAs($demo);

    $this->post(route('blog.categorias.store'), ['nombre' => 'Intento'])->assertForbidden();
    $this->post(route('blog.etiquetas.store'), ['nombre' => 'intento'])->assertForbidden();
    $this->get(route('config.users.index'))->assertForbidden();
});

test('el rol demo no es super administrador', function () {
    $demo = usuarioDemo();

    expect($demo->fresh()->es_super_admin)->toBeFalse()
        ->and($demo->can(Permiso::UsuariosGestionar->value))->toBeFalse();
});

test('el seeder crea la cuenta demo con su rol', function () {
    config()->set('plataforma.demo.email', 'demo@example.com');
    config()->set('plataforma.demo.password', 'una-clave-de-prueba');

    (new RoleSeeder)->run();
    (new UsuarioDemoSeeder)->run();

    $demo = User::query()->where('email', 'demo@example.com')->firstOrFail();

    expect($demo->hasRole(Rol::Demo->value))->toBeTrue()
        ->and($demo->es_super_admin)->toBeFalse()
        ->and($demo->getAllPermissions())->toHaveCount(count(Rol::Demo->permisos()));
});

test('sin clave en el entorno la cuenta demo no se crea', function () {
    config()->set('plataforma.demo.email', 'demo@example.com');
    config()->set('plataforma.demo.password', null);

    (new RoleSeeder)->run();
    (new UsuarioDemoSeeder)->run();

    expect(User::query()->where('email', 'demo@example.com')->exists())->toBeFalse();
});
