<?php

use App\Enums\EstadoPublicacion;
use App\Enums\Permiso;
use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\Post;
use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

function usuarioConPermiso(string ...$permisos): User
{
    foreach ($permisos as $permiso) {
        Permission::findOrCreate($permiso);
    }

    $usuario = User::factory()->create();
    $usuario->givePermissionTo($permisos);

    return $usuario;
}

/**
 * Un editor completo: consulta, captura, publica y elimina. Se arma desde el
 * enum del rol para que separar un permiso más no vuelva a romper cada prueba.
 */
function usuarioEditor(): User
{
    return usuarioConPermiso(...array_map(
        fn (Permiso $permiso): string => $permiso->value,
        Rol::Editor->permisos(),
    ));
}

test('un usuario sin permiso no entra al listado', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('blog.publicaciones.index', ['tipo' => 'posts']))
        ->assertForbidden();
});

test('el listado muestra las publicaciones del tipo pedido', function () {
    $usuario = usuarioEditor();
    $this->actingAs($usuario);

    Post::query()->create([
        'titulo' => 'Post de prueba',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
        'id_autor' => $usuario->id,
    ]);

    Tutorial::query()->create([
        'titulo' => 'Tutorial de prueba',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
        'id_autor' => $usuario->id,
    ]);

    $this->get(route('blog.publicaciones.index', ['tipo' => 'posts']))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('blog/publicaciones/index')
            ->has('publicaciones', 1)
            ->where('publicaciones.0.titulo', 'Post de prueba'));
});

test('store crea la publicacion con slug y tiempo de lectura calculados', function () {
    config()->set('blog.disco', 'publicaciones');
    Storage::fake('publicaciones');

    $usuario = usuarioEditor();
    $this->actingAs($usuario);

    $categoria = Categoria::query()->create(['nombre' => 'Laravel', 'slug' => 'laravel']);
    $etiqueta = Etiqueta::query()->create(['nombre' => 'php', 'slug' => 'php']);

    $this->post(route('blog.publicaciones.store', ['tipo' => 'posts']), [
        'titulo' => 'Mi primer proyecto',
        'tags_seo' => 'laravel, php',
        'estado' => EstadoPublicacion::Borrador->value,
        'id_autor' => $usuario->id,
        'id_categoria' => $categoria->id,
        'etiquetas' => [$etiqueta->id],
        'imagen' => UploadedFile::fake()->image('portada.jpg'),
    ])->assertRedirect();

    $post = Post::query()->firstOrFail();

    expect($post->slug)->toBe('mi-primer-proyecto')
        ->and($post->tiempo_lectura)->toBe(1)
        ->and($post->etiquetas)->toHaveCount(1)
        ->and($post->imagen_destacada)->not->toBeNull();

    Storage::disk('publicaciones')->assertExists($post->imagen_destacada);
});

test('store con id actualiza en lugar de crear', function () {
    $usuario = usuarioEditor();
    $this->actingAs($usuario);

    $post = Post::query()->create([
        'titulo' => 'Título viejo',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
        'id_autor' => $usuario->id,
    ]);

    $this->post(route('blog.publicaciones.store', ['tipo' => 'posts']), [
        'id' => $post->id,
        'titulo' => 'Título nuevo',
        'slug' => $post->slug,
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador->value,
        'id_autor' => $usuario->id,
    ])->assertRedirect();

    expect(Post::query()->count())->toBe(1)
        ->and($post->fresh()->titulo)->toBe('Título nuevo');
});

test('el slug se hace unico cuando ya existe', function () {
    $usuario = usuarioEditor();

    $primero = Post::query()->create([
        'titulo' => 'Guía de despliegue',
        'tags_seo' => 'devops',
        'estado' => EstadoPublicacion::Borrador,
        'id_autor' => $usuario->id,
    ]);

    $segundo = Post::query()->create([
        'titulo' => 'Guía de despliegue',
        'tags_seo' => 'devops',
        'estado' => EstadoPublicacion::Borrador,
        'id_autor' => $usuario->id,
    ]);

    expect($primero->slug)->toBe('guia-de-despliegue')
        ->and($segundo->slug)->toBe('guia-de-despliegue-2');
});

test('el contenido se guarda desde su propia pantalla', function () {
    $usuario = usuarioEditor();
    $this->actingAs($usuario);

    $post = Post::query()->create([
        'titulo' => 'Post con contenido',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
        'id_autor' => $usuario->id,
    ]);

    $this->post(
        route('blog.publicaciones.contenido.store', ['tipo' => 'posts', 'publicacion' => $post->id]),
        ['contenido' => '<p>'.str_repeat('palabra ', 400).'</p>'],
    )->assertRedirect();

    expect($post->fresh()->tiempo_lectura)->toBe(2);
});

test('destroy borra la publicacion', function () {
    $usuario = usuarioEditor();
    $this->actingAs($usuario);

    $post = Post::query()->create([
        'titulo' => 'Post desechable',
        'tags_seo' => 'laravel',
        'estado' => EstadoPublicacion::Borrador,
        'id_autor' => $usuario->id,
    ]);

    $this->delete(route('blog.publicaciones.destroy', ['tipo' => 'posts', 'publicacion' => $post->id]))
        ->assertRedirect();

    expect(Post::query()->count())->toBe(0);
});
