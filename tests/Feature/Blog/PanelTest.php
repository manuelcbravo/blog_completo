<?php

use App\Models\User;

function superAdmin(): User
{
    $usuario = User::factory()->create();
    $usuario->forceFill(['es_super_admin' => true])->save();

    return $usuario;
}

test('todas las pantallas del blog abren para el super admin', function (string $ruta, array $parametros, string $componente) {
    $this->actingAs(superAdmin());

    $this->get(route($ruta, $parametros))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina->component($componente));
})->with([
    ['blog.publicaciones.index', ['tipo' => 'posts'], 'blog/publicaciones/index'],
    ['blog.publicaciones.index', ['tipo' => 'tutoriales'], 'blog/publicaciones/index'],
    ['blog.publicaciones.index', ['tipo' => 'recursos'], 'blog/publicaciones/index'],
    ['blog.categorias.index', [], 'blog/categorias/index'],
    ['blog.etiquetas.index', [], 'blog/etiquetas/index'],
    ['blog.comentarios.index', [], 'blog/comentarios/index'],
    ['blog.suscriptores.index', [], 'blog/suscriptores/index'],
    ['blog.contactos.index', [], 'blog/contactos/index'],
]);

test('un tipo de publicacion inventado da 404', function () {
    $this->actingAs(superAdmin());

    $this->get('/blog/publicaciones/inventado')->assertNotFound();
});

test('el dashboard trae la analitica para quien puede verla', function () {
    $this->actingAs(superAdmin());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('dashboard')
            ->has('analitica.resumen')
            ->has('analitica.serieVistas', 30)
            ->has('analitica.porTipo', 3));
});

test('el dashboard no expone analitica a quien no tiene el permiso', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('dashboard')
            ->where('analitica', null));
});

test('la api publica responde el ping', function () {
    $this->getJson(route('api.blog.salud'))
        ->assertOk()
        ->assertJson(['ok' => true]);
});
