<?php

use App\Enums\EstadoSuscriptor;
use App\Mail\BienvenidaSuscriptor;
use App\Mail\ConfirmacionSuscripcion;
use App\Models\Suscriptor;
use Illuminate\Support\Facades\Mail;

test('el alta publica deja al suscriptor pendiente y manda el correo de confirmacion', function () {
    Mail::fake();

    $this->postJson(route('api.blog.suscriptores.store'), [
        'email' => 'lector@example.com',
        'nombre' => 'Lector',
        'origen' => 'footer',
    ])->assertCreated();

    $suscriptor = Suscriptor::query()->firstOrFail();

    expect($suscriptor->estado)->toBe(EstadoSuscriptor::Pendiente)
        ->and($suscriptor->token)->not->toBeNull();

    Mail::assertQueued(ConfirmacionSuscripcion::class);
});

test('el honeypot bloquea el alta', function () {
    Mail::fake();

    $this->postJson(route('api.blog.suscriptores.store'), [
        'email' => 'bot@example.com',
        'sitio_web' => 'http://spam.example',
    ])->assertStatus(422);

    expect(Suscriptor::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

test('el enlace de confirmacion activa la suscripcion y manda la bienvenida', function () {
    Mail::fake();

    $suscriptor = Suscriptor::query()->create([
        'email' => 'lector@example.com',
        'estado' => EstadoSuscriptor::Pendiente,
        'token' => Suscriptor::nuevoToken(),
    ]);

    $this->get(route('suscripcion.confirmar', ['token' => $suscriptor->token]))
        ->assertOk()
        ->assertSee('Suscripción confirmada');

    expect($suscriptor->fresh()->estado)->toBe(EstadoSuscriptor::Confirmado)
        ->and($suscriptor->fresh()->confirmado_at)->not->toBeNull();

    Mail::assertQueued(BienvenidaSuscriptor::class);
});

test('el enlace de baja cancela la suscripcion', function () {
    $suscriptor = Suscriptor::query()->create([
        'email' => 'lector@example.com',
        'estado' => EstadoSuscriptor::Confirmado,
        'token' => Suscriptor::nuevoToken(),
        'confirmado_at' => now(),
    ]);

    $this->get(route('suscripcion.baja', ['token' => $suscriptor->token]))
        ->assertOk()
        ->assertSee('Suscripción cancelada');

    expect($suscriptor->fresh()->estado)->toBe(EstadoSuscriptor::Baja);
});

test('un token inexistente no revienta', function () {
    $this->get(route('suscripcion.confirmar', ['token' => 'inventado']))
        ->assertOk()
        ->assertSee('Enlace no válido');
});
