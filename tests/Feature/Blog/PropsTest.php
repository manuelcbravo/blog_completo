<?php

use App\Enums\EstadoPublicacion;
use App\Models\Categoria;
use App\Models\Contacto;
use App\Models\Etiqueta;
use App\Models\Post;
use App\Models\User;

/**
 * Las relaciones anidadas se serializan con ->resolve(): un JsonResource
 * implementa Responsable, así que Inertia lo convertiría en {"data": [...]}
 * y la página recibiría un objeto donde el tipo promete un arreglo.
 */
function superAdminDePruebas(): User
{
    $usuario = User::factory()->create();
    $usuario->forceFill(['es_super_admin' => true])->save();

    return $usuario;
}

test('las publicaciones llegan con relaciones planas, no envueltas en data', function () {
    $this->actingAs(superAdminDePruebas());

    $categoria = Categoria::query()->create(['nombre' => 'Colas', 'slug' => 'colas']);
    $etiqueta = Etiqueta::query()->create(['nombre' => 'php', 'slug' => 'php']);

    $post = Post::query()->create([
        'titulo' => 'Con relaciones',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Publicado,
        'id_categoria' => $categoria->id,
    ]);
    $post->etiquetas()->sync([$etiqueta->id]);

    $this->get(route('blog.publicaciones.index', ['tipo' => 'posts']))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->has('publicaciones.0.etiquetas', 1)
            ->where('publicaciones.0.etiquetas.0.nombre', 'php')
            ->where('publicaciones.0.categoria.nombre', 'Colas')
            ->where('publicaciones.0.comentarios', 0)
            ->has('publicaciones.0.detalles', 0));
});

test('una publicación sin relaciones trae arreglos vacíos, no claves ausentes', function () {
    $this->actingAs(superAdminDePruebas());

    Post::query()->create([
        'titulo' => 'Sin nada',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
    ]);

    $this->get(route('blog.publicaciones.index', ['tipo' => 'posts']))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->has('publicaciones.0.etiquetas', 0)
            ->where('publicaciones.0.categoria', null)
            ->where('publicaciones.0.autor', null));
});

test('los usuarios llegan con sus roles como arreglo', function () {
    $usuario = superAdminDePruebas();
    $this->actingAs($usuario);

    $this->get(route('config.users.index'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina->has('users.0.roles'));
});

test('los contactos llegan con el responsable plano', function () {
    $usuario = superAdminDePruebas();
    $this->actingAs($usuario);

    Contacto::query()->create([
        'name' => 'Prospecto',
        'email' => 'p@example.com',
        'message' => 'Hola',
        'respondido_por' => $usuario->id,
    ]);

    $this->get(route('blog.contactos.index'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->where('contactos.0.responsable.nombre', $usuario->name));
});
