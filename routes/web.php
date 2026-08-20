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
use App\Http\Controllers\Blog\VisitaController;
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
    Route::get('privacidad', [SitioController::class, 'privacidad'])->name('privacidad');
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
Route::get('robots.txt', [FeedController::class, 'robots'])->name('robots');

Route::get('suscripcion/confirmar/{token}', [SuscripcionController::class, 'confirmar'])
    ->name('suscripcion.confirmar');

Route::get('suscripcion/baja/{token}', [SuscripcionController::class, 'baja'])
    ->name('suscripcion.baja');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [AnaliticaController::class, 'index'])->name('dashboard');

    Route::prefix('blog')->name('blog.')->group(function () {
        /*
         * Las publicaciones se reparten en cuatro grupos y no en uno, para que
         * exista un perfil que consulte y capture sin poder publicar ni borrar.
         * Cada accion irreversible -sacar algo al sitio y eliminarlo- pide su
         * propio permiso.
         */
        Route::prefix('publicaciones/{tipo}')
            ->whereIn('tipo', TipoPublicacion::segmentos())
            ->name('publicaciones.')
            ->group(function () {
                Route::middleware('can:blog.publicaciones.ver')->group(function () {
                    Route::get('/', [PublicacionController::class, 'index'])->name('index');
                    Route::get('{publicacion}/contenido', [ContenidoController::class, 'index'])->name('contenido.index');
                });

                Route::middleware('can:blog.publicaciones.gestionar')->group(function () {
                    Route::post('/', [PublicacionController::class, 'store'])->name('store');
                    Route::post('{publicacion}/contenido', [ContenidoController::class, 'store'])->name('contenido.store');
                    Route::post('contenido/imagen', [ContenidoController::class, 'imagen'])->name('contenido.imagen');
                });

                Route::middleware('can:blog.publicaciones.publicar')
                    ->post('{publicacion}/estado', [EstadoController::class, 'store'])
                    ->name('estado.store');

                Route::middleware('can:blog.publicaciones.eliminar')
                    ->delete('{publicacion}', [PublicacionController::class, 'destroy'])
                    ->name('destroy');
            });

        Route::prefix('recursos/{recurso}/detalles')->name('detalles.')->group(function () {
            Route::middleware('can:blog.publicaciones.gestionar')
                ->post('/', [DetalleController::class, 'store'])
                ->name('store');

            Route::middleware('can:blog.publicaciones.eliminar')
                ->delete('{detalle}', [DetalleController::class, 'destroy'])
                ->name('destroy');
        });

        Route::middleware('can:blog.taxonomias.ver')->group(function () {
            Route::get('categorias', [CategoriaController::class, 'index'])->name('categorias.index');
            Route::get('etiquetas', [EtiquetaController::class, 'index'])->name('etiquetas.index');
        });

        Route::middleware('can:blog.taxonomias.gestionar')->group(function () {
            Route::post('categorias', [CategoriaController::class, 'store'])->name('categorias.store');
            Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

            Route::post('etiquetas', [EtiquetaController::class, 'store'])->name('etiquetas.store');
            Route::delete('etiquetas/{etiqueta}', [EtiquetaController::class, 'destroy'])->name('etiquetas.destroy');
        });

        Route::middleware('can:blog.comentarios.ver')
            ->get('comentarios', [ComentarioController::class, 'index'])
            ->name('comentarios.index');

        Route::middleware('can:blog.comentarios.moderar')->group(function () {
            Route::post('comentarios', [ComentarioController::class, 'store'])->name('comentarios.store');
            Route::delete('comentarios/{comentario}', [ComentarioController::class, 'destroy'])->name('comentarios.destroy');
        });

        Route::middleware('can:blog.suscriptores.gestionar')->group(function () {
            Route::get('suscriptores', [SuscriptorController::class, 'index'])->name('suscriptores.index');
            Route::post('suscriptores', [SuscriptorController::class, 'store'])->name('suscriptores.store');
            Route::delete('suscriptores/{suscriptor}', [SuscriptorController::class, 'destroy'])->name('suscriptores.destroy');
        });

        Route::middleware('can:blog.visitas.ver')->group(function () {
            Route::get('visitas', [VisitaController::class, 'index'])->name('visitas.index');
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
