<?php

use App\Enums\Permiso;
use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function editorDeTaxonomias(): User
{
    Permission::findOrCreate(Permiso::BlogTaxonomiasGestionar->value);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(Permiso::BlogTaxonomiasGestionar->value);

    return $usuario;
}

test('sin permiso no se entra a categorias', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('blog.categorias.index'))->assertForbidden();
});

test('la categoria se crea con slug automatico', function () {
    $this->actingAs(editorDeTaxonomias());

    $this->post(route('blog.categorias.store'), [
        'nombre' => 'Infraestructura y DevOps',
    ])->assertRedirect();

    expect(Categoria::query()->firstOrFail()->slug)->toBe('infraestructura-y-devops');
});

test('no se repite el nombre de la categoria', function () {
    $this->actingAs(editorDeTaxonomias());

    Categoria::query()->create(['nombre' => 'Laravel', 'slug' => 'laravel']);

    $this->post(route('blog.categorias.store'), ['nombre' => 'Laravel'])
        ->assertSessionHasErrors('nombre');
});

test('la etiqueta se edita conservando el id', function () {
    $this->actingAs(editorDeTaxonomias());

    $etiqueta = Etiqueta::query()->create(['nombre' => 'php', 'slug' => 'php']);

    $this->post(route('blog.etiquetas.store'), [
        'id' => $etiqueta->id,
        'nombre' => 'php 8',
        'slug' => 'php-8',
    ])->assertRedirect();

    expect(Etiqueta::query()->count())->toBe(1)
        ->and($etiqueta->fresh()->nombre)->toBe('php 8');
});

test('la etiqueta se elimina', function () {
    $this->actingAs(editorDeTaxonomias());

    $etiqueta = Etiqueta::query()->create(['nombre' => 'temporal', 'slug' => 'temporal']);

    $this->delete(route('blog.etiquetas.destroy', ['etiqueta' => $etiqueta->id]))
        ->assertRedirect();

    expect(Etiqueta::query()->count())->toBe(0);
});
