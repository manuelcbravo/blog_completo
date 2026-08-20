<?php

use App\Enums\EstadoPublicacion;
use App\Models\Categoria;
use App\Models\Comentario;
use App\Models\Etiqueta;
use App\Models\Post;
use App\Models\Recurso;
use App\Models\Tutorial;
use App\Models\User;
use App\Models\Vista;
use App\Support\Redaccion\Borrador;
use App\Support\Redaccion\ImportadorDeBorradores;
use Illuminate\Support\Facades\File;

function directorioDeBorradores(): string
{
    $ruta = storage_path('framework/testing/borradores-'.uniqid());
    File::ensureDirectoryExists($ruta);

    return $ruta;
}

function escribirBorrador(string $directorio, string $nombre, string $contenido): string
{
    $ruta = $directorio.'/'.$nombre;
    File::put($ruta, $contenido);

    return $ruta;
}

test('todos los borradores del repositorio son válidos', function () {
    $borradores = ImportadorDeBorradores::porDefecto()->borradores();

    expect($borradores)->not->toBeEmpty();

    foreach ($borradores as $borrador) {
        expect($borrador->titulo())->not->toBe('Sin título')
            ->and($borrador->slug())->not->toBe('')
            ->and($borrador->resumen())->not->toBe('')
            ->and($borrador->tiempoLectura())->toBeGreaterThan(0);
    }
});

test('el encabezado se convierte en los campos de la publicación', function () {
    $directorio = directorioDeBorradores();

    $ruta = escribirBorrador($directorio, 'prueba.md', <<<'MD'
        ---
        titulo: 'Un título: con dos puntos'
        tipo: tutorial
        estado: publicado
        categoria: Pruebas
        etiquetas: [laravel, pest]
        resumen: El resumen corto.
        hace_dias: 3
        importante: true
        ---

        ## Un encabezado

        Un párrafo con `código`.
        MD);

    $borrador = Borrador::desdeArchivo($ruta);

    expect($borrador->titulo())->toBe('Un título: con dos puntos')
        ->and($borrador->slug())->toBe('un-titulo-con-dos-puntos')
        ->and($borrador->tipo()->value)->toBe('tutorial')
        ->and($borrador->estado())->toBe(EstadoPublicacion::Publicado)
        ->and($borrador->etiquetas())->toBe(['laravel', 'pest'])
        ->and($borrador->importante())->toBeTrue()
        ->and($borrador->haceDias())->toBe(3)
        ->and($borrador->metaTitulo())->toBe('Un título: con dos puntos');

    expect($borrador->contenidoHtml())
        ->toContain('<h2>Un encabezado</h2>')
        ->toContain('<code>código</code>');
});

test('un borrador sin encabezado se rechaza con un mensaje claro', function () {
    $directorio = directorioDeBorradores();
    $ruta = escribirBorrador($directorio, 'roto.md', "Sin encabezado.\n");

    expect(fn () => Borrador::desdeArchivo($ruta))
        ->toThrow(RuntimeException::class, 'no empieza con el encabezado');
});

test('el importador crea la publicación con su categoría y etiquetas', function () {
    User::factory()->create();
    $directorio = directorioDeBorradores();

    escribirBorrador($directorio, 'uno.md', <<<'MD'
        ---
        titulo: Colas en Laravel
        tipo: post
        estado: publicado
        categoria: Arquitectura
        etiquetas: [laravel, colas]
        resumen: Sobre colas.
        hace_dias: 5
        ---

        Contenido.
        MD);

    $resultado = (new ImportadorDeBorradores($directorio))->importar();

    expect($resultado['creadas'])->toBe(1)
        ->and($resultado['actualizadas'])->toBe(0);

    $post = Post::query()->firstOrFail();

    expect($post->titulo)->toBe('Colas en Laravel')
        ->and($post->slug)->toBe('colas-en-laravel')
        ->and($post->estado)->toBe(EstadoPublicacion::Publicado)
        ->and($post->fecha_publicacion?->toDateString())->toBe(now()->subDays(5)->toDateString())
        ->and($post->categoria?->nombre)->toBe('Arquitectura')
        ->and($post->etiquetas->pluck('nombre')->all())->toBe(['laravel', 'colas']);
});

test('volver a importar actualiza en vez de duplicar', function () {
    User::factory()->create();
    $directorio = directorioDeBorradores();

    $contenido = <<<'MD'
        ---
        titulo: Título original
        slug: titulo-fijo
        tipo: post
        estado: publicado
        resumen: Primera versión.
        ---

        Contenido.
        MD;

    escribirBorrador($directorio, 'uno.md', $contenido);

    $importador = new ImportadorDeBorradores($directorio);
    $importador->importar();

    escribirBorrador($directorio, 'uno.md', str_replace(
        ['Título original', 'Primera versión.'],
        ['Título corregido', 'Segunda versión.'],
        $contenido,
    ));

    $resultado = $importador->importar();

    expect($resultado['creadas'])->toBe(0)
        ->and($resultado['actualizadas'])->toBe(1)
        ->and(Post::query()->count())->toBe(1);

    $post = Post::query()->firstOrFail();

    expect($post->titulo)->toBe('Título corregido')
        ->and($post->resumen)->toBe('Segunda versión.');
});

test('un borrador en revisión no sale al sitio público', function () {
    User::factory()->create();
    $directorio = directorioDeBorradores();

    escribirBorrador($directorio, 'uno.md', <<<'MD'
        ---
        titulo: Todavía no
        tipo: post
        estado: revision
        resumen: No debería verse.
        ---

        Contenido.
        MD);

    (new ImportadorDeBorradores($directorio))->importar();

    $post = Post::query()->firstOrFail();

    expect($post->estado)->toBe(EstadoPublicacion::Revision)
        ->and($post->fecha_publicacion)->toBeNull();

    $this->get(route('publico.articulo', ['slug' => $post->slug]))->assertNotFound();
    $this->get(route('publico.articulos'))->assertOk()->assertDontSee('Todavía no');
});

test('la limpieza borra lo que no tiene borrador, con sus vistas y comentarios', function () {
    $autor = User::factory()->create();
    $directorio = directorioDeBorradores();

    escribirBorrador($directorio, 'uno.md', <<<'MD'
        ---
        titulo: El que se queda
        tipo: post
        estado: publicado
        resumen: Tiene borrador.
        ---

        Contenido.
        MD);

    $sobrante = Post::query()->create([
        'slug' => 'el-que-sobra',
        'titulo' => 'El que sobra',
        'estado' => EstadoPublicacion::Publicado,
        'id_autor' => $autor->id,
    ]);

    Vista::query()->create(['post_id' => $sobrante->id, 'tipo' => 'post', 'ip_address' => '1.1.1.1']);
    Comentario::query()->create([
        'post_id' => $sobrante->id,
        'tipo' => 'post',
        'nombre' => 'Alguien',
        'correo' => 'alguien@example.com',
        'contenido' => 'Un comentario de relleno.',
    ]);

    $importador = new ImportadorDeBorradores($directorio);
    $importador->importar();
    $eliminadas = $importador->limpiarLoQueNoEsBorrador();

    expect($eliminadas)->toContain('El que sobra')
        ->and(Post::query()->withTrashed()->pluck('slug')->all())->toBe(['el-que-se-queda'])
        ->and(Vista::query()->where('tipo', 'post')->count())->toBe(0)
        ->and(Comentario::query()->count())->toBe(0);
});

test('la limpieza retira las categorías y etiquetas que quedaron vacías', function () {
    User::factory()->create();
    $directorio = directorioDeBorradores();

    escribirBorrador($directorio, 'uno.md', <<<'MD'
        ---
        titulo: El que se queda
        tipo: post
        estado: publicado
        categoria: Usada
        etiquetas: [usada]
        resumen: Tiene borrador.
        ---

        Contenido.
        MD);

    Categoria::query()->create(['nombre' => 'Huérfana', 'slug' => 'huerfana']);
    Etiqueta::query()->create(['nombre' => 'huerfana', 'slug' => 'huerfana']);

    $importador = new ImportadorDeBorradores($directorio);
    $importador->importar();
    $importador->limpiarLoQueNoEsBorrador();

    expect(Categoria::query()->pluck('nombre')->all())->toBe(['Usada'])
        ->and(Etiqueta::query()->pluck('nombre')->all())->toBe(['usada']);
});

test('el comando revisa sin escribir nada', function () {
    User::factory()->create();

    $this->artisan('blog:redaccion', ['--revisar' => true])->assertSuccessful();

    expect(Post::query()->count() + Tutorial::query()->count() + Recurso::query()->count())->toBe(0);
});
