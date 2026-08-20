<?php

use App\Enums\TipoPublicacion;
use App\Http\Controllers\Blog\AnaliticaController;
use App\Http\Controllers\Blog\CategoriaController;
use App\Http\Controllers\Blog\ComentarioController;
use App\Http\Controllers\Blog\ContactoController;
use App\Http\Controllers\Blog\ContenidoController;
use App\Http\Controllers\Blog\DetalleController;
use App\Http\Controllers\Blog\EstadoController;
use App\Http\Controllers\Blog\EtiquetaController;
use App\Http\Controllers\Blog\PublicacionController;
use App\Http\Controllers\Blog\SuscriptorController;
use App\Http\Controllers\Config\RoleController;
use App\Http\Controllers\Config\UserController;
use App\Http\Controllers\Publico\FeedController;
use App\Http\Controllers\Publico\SitioController;
use App\Http\Controllers\Publico\SuscripcionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SitioController::class, 'home'])->name('home');

Route::name('publico.')->group(function () {
    Route::get('articulos', [SitioController::class, 'articulos'])->name('articulos');
    Route::get('articulos/{slug}', [SitioController::class, 'articulo'])->name('articulo');
    Route::get('tutoriales', [SitioController::class, 'tutoriales'])->name('tutoriales');
    Route::get('tutoriales/{slug}', [SitioController::class, 'tutorial'])->name('tutorial');
    Route::get('recursos', [SitioController::class, 'recursos'])->name('recursos');
    Route::get('recursos/{slug}', [SitioController::class, 'recurso'])->name('recurso');
    Route::get('proyectos', [SitioController::class, 'proyectos'])->name('proyectos');
    Route::get('categoria/{slug}', [SitioController::class, 'categoria'])->name('categoria');
    Route::get('buscar', [SitioController::class, 'buscar'])->name('buscar');
    Route::get('newsletter', [SitioController::class, 'newsletter'])->name('newsletter');
    Route::get('sobre', [SitioController::class, 'sobre'])->name('sobre');
    Route::get('manuel', [SitioController::class, 'autor'])->name('autor');

    Route::post('newsletter', [SitioController::class, 'suscribir'])
        ->middleware('throttle:10,1')
        ->name('suscribir');

    Route::post('contacto', [SitioController::class, 'contactar'])
        ->middleware('throttle:10,1')
        ->name('contactar');

    Route::post('comentarios', [SitioController::class, 'comentar'])
        ->middleware('throttle:10,1')
        ->name('comentar');
});

Route::get('feed', [FeedController::class, 'feed'])->name('feed');
Route::get('sitemap.xml', [FeedController::class, 'sitemap'])->name('sitemap');

Route::get('suscripcion/confirmar/{token}', [SuscripcionController::class, 'confirmar'])
    ->name('suscripcion.confirmar');

Route::get('suscripcion/baja/{token}', [SuscripcionController::class, 'baja'])
    ->name('suscripcion.baja');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [AnaliticaController::class, 'index'])->name('dashboard');

    Route::prefix('blog')->name('blog.')->group(function () {
        Route::middleware('can:blog.publicaciones.gestionar')->group(function () {
            Route::prefix('publicaciones/{tipo}')
                ->whereIn('tipo', TipoPublicacion::segmentos())
                ->name('publicaciones.')
                ->group(function () {
                    Route::get('/', [PublicacionController::class, 'index'])->name('index');
                    Route::post('/', [PublicacionController::class, 'store'])->name('store');
                    Route::delete('{publicacion}', [PublicacionController::class, 'destroy'])->name('destroy');
                    Route::get('{publicacion}/contenido', [ContenidoController::class, 'index'])->name('contenido.index');
                    Route::post('{publicacion}/contenido', [ContenidoController::class, 'store'])->name('contenido.store');
                    Route::post('contenido/imagen', [ContenidoController::class, 'imagen'])->name('contenido.imagen');
                    Route::post('{publicacion}/estado', [EstadoController::class, 'store'])->name('estado.store');
                });

            Route::prefix('recursos/{recurso}/detalles')->name('detalles.')->group(function () {
                Route::post('/', [DetalleController::class, 'store'])->name('store');
                Route::delete('{detalle}', [DetalleController::class, 'destroy'])->name('destroy');
            });
        });

        Route::middleware('can:blog.taxonomias.gestionar')->group(function () {
            Route::get('categorias', [CategoriaController::class, 'index'])->name('categorias.index');
            Route::post('categorias', [CategoriaController::class, 'store'])->name('categorias.store');
            Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

            Route::get('etiquetas', [EtiquetaController::class, 'index'])->name('etiquetas.index');
            Route::post('etiquetas', [EtiquetaController::class, 'store'])->name('etiquetas.store');
            Route::delete('etiquetas/{etiqueta}', [EtiquetaController::class, 'destroy'])->name('etiquetas.destroy');
        });

        Route::middleware('can:blog.comentarios.moderar')->group(function () {
            Route::get('comentarios', [ComentarioController::class, 'index'])->name('comentarios.index');
            Route::post('comentarios', [ComentarioController::class, 'store'])->name('comentarios.store');
            Route::delete('comentarios/{comentario}', [ComentarioController::class, 'destroy'])->name('comentarios.destroy');
        });

        Route::middleware('can:blog.suscriptores.gestionar')->group(function () {
            Route::get('suscriptores', [SuscriptorController::class, 'index'])->name('suscriptores.index');
            Route::post('suscriptores', [SuscriptorController::class, 'store'])->name('suscriptores.store');
            Route::delete('suscriptores/{suscriptor}', [SuscriptorController::class, 'destroy'])->name('suscriptores.destroy');
        });

        Route::middleware('can:blog.contactos.gestionar')->group(function () {
            Route::get('contactos', [ContactoController::class, 'index'])->name('contactos.index');
            Route::post('contactos', [ContactoController::class, 'store'])->name('contactos.store');
            Route::delete('contactos/{contacto}', [ContactoController::class, 'destroy'])->name('contactos.destroy');
        });
    });

    Route::prefix('config')->middleware('can:usuarios.gestionar')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('config.users.index');
        Route::post('users', [UserController::class, 'store'])->name('config.users.store');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('config.users.destroy');
        Route::get('roles', [RoleController::class, 'index'])->name('config.roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('config.roles.store');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('config.roles.destroy');
    });
});

require __DIR__.'/settings.php';
