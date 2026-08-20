<?php

use App\Http\Controllers\Publico\InteraccionController;
use App\Http\Controllers\Publico\SuscripcionController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->name('api.blog.')->group(function () {
    Route::get('salud', [InteraccionController::class, 'salud'])->name('salud');

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('suscriptores', [SuscripcionController::class, 'store'])->name('suscriptores.store');
        Route::post('comentarios', [InteraccionController::class, 'comentar'])->name('comentarios.store');
        Route::post('contactos', [InteraccionController::class, 'contactar'])->name('contactos.store');
    });

    Route::middleware('throttle:120,1')
        ->post('vistas', [InteraccionController::class, 'registrarVista'])
        ->name('vistas.store');
});
