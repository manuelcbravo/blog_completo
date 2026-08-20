<?php

use App\Enums\EstadoPublicacion;
use App\Enums\Permiso;
use App\Enums\SeccionSitio;
use App\Models\Post;
use App\Models\User;
use App\Models\Vista;
use Spatie\Permission\Models\Permission;

function usuarioConAnalitica(): User
{
    Permission::findOrCreate(Permiso::BlogVisitasVer->value);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(Permiso::BlogVisitasVer->value);

    return $usuario;
}

test('la ficha del autor registra la visita con su rastro', function () {
    $this->get(route('publico.autor'))->assertOk();

    $visita = Vista::query()->firstOrFail();

    expect($visita->tipo)->toBe(SeccionSitio::Autor->value)
        ->and($visita->post_id)->toBeNull()
        ->and($visita->ruta)->toBe('manuel')
        ->and($visita->ip_address)->not->toBeNull()
        ->and($visita->session_id)->not->toBeNull();
});

test('los proyectos registran la visita', function () {
    $this->get(route('publico.proyectos'))->assertOk();

    expect(Vista::query()->where('tipo', SeccionSitio::Proyectos->value)->count())->toBe(1);
});

test('recargar la misma página no infla el conteo', function () {
    $this->get(route('publico.autor'))->assertOk();
    $this->get(route('publico.autor'))->assertOk();
    $this->get(route('publico.autor'))->assertOk();

    expect(Vista::query()->count())->toBe(1);
});

test('la bitácora lista las visitas y resuelve el título', function () {
    $usuario = usuarioConAnalitica();

    $post = Post::query()->create([
        'titulo' => 'Cómo desplegar Laravel',
        'slug' => 'como-desplegar-laravel',
        'estado' => EstadoPublicacion::Publicado,
        'id_autor' => $usuario->id,
    ]);

    Vista::query()->create([
        'post_id' => $post->id,
        'tipo' => 'post',
        'ruta' => 'articulos/como-desplegar-laravel',
        'ip_address' => '189.203.10.7',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit/605.1.15 Safari/604.1',
        'referer' => 'https://www.linkedin.com/feed/',
        'session_id' => 'sesion-de-prueba',
    ]);

    Vista::query()->create([
        'post_id' => null,
        'tipo' => SeccionSitio::Autor->value,
        'ruta' => 'manuel',
        'ip_address' => '201.141.2.9',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0 Safari/537.36',
        'session_id' => 'otra-sesion',
    ]);

    $this->actingAs($usuario)
        ->get(route('blog.visitas.index'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('blog/visitas/index')
            ->has('visitas', 2)
            ->where('visitas.0.titulo', 'Ficha del autor')
            ->where('visitas.0.origen', 'Directo')
            ->where('visitas.0.navegador', 'Chrome · Windows')
            ->where('visitas.1.titulo', 'Cómo desplegar Laravel')
            ->where('visitas.1.origen', 'linkedin.com')
            ->where('visitas.1.navegador', 'Safari · iOS')
            ->where('visitas.1.ip', '189.203.10.7')
        );
});

test('la bitácora filtra por tipo', function () {
    $usuario = usuarioConAnalitica();

    Vista::query()->create(['post_id' => null, 'tipo' => SeccionSitio::Autor->value, 'ip_address' => '1.1.1.1']);
    Vista::query()->create(['post_id' => null, 'tipo' => SeccionSitio::Proyectos->value, 'ip_address' => '2.2.2.2']);

    $this->actingAs($usuario)
        ->get(route('blog.visitas.index', ['tipo' => SeccionSitio::Proyectos->value]))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina->has('visitas', 1));
});

test('un usuario sin permiso no entra a la bitácora', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('blog.visitas.index'))->assertForbidden();
});

test('el aviso de privacidad es público y está enlazado en el pie', function () {
    $this->get(route('publico.privacidad'))
        ->assertOk()
        ->assertSee('Aviso de privacidad');

    $this->get(route('publico.autor'))
        ->assertOk()
        ->assertSee(route('publico.privacidad'), false);
});
