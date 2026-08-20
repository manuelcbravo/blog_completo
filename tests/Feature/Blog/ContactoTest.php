<?php

use App\Enums\EstadoContacto;
use App\Enums\Permiso;
use App\Mail\AcuseContacto;
use App\Mail\NuevoContacto;
use App\Mail\RespuestaContacto;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;

function agenteDeContacto(): User
{
    Permission::findOrCreate(Permiso::BlogContactosGestionar->value);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(Permiso::BlogContactosGestionar->value);

    return $usuario;
}

test('el formulario publico guarda el mensaje y manda los dos correos', function () {
    Mail::fake();
    config()->set('blog.admin_email', 'admin@example.com');

    $this->postJson(route('api.blog.contactos.store'), [
        'nombre' => 'Prospecto',
        'email' => 'prospecto@example.com',
        'mensaje' => 'Quiero una cotización para un proyecto como el tuyo.',
    ])->assertCreated();

    $contacto = Contacto::query()->firstOrFail();

    expect($contacto->estado)->toBe(EstadoContacto::Nuevo)
        ->and($contacto->ip_address)->not->toBeNull();

    Mail::assertQueued(NuevoContacto::class);
    Mail::assertQueued(AcuseContacto::class);
});

test('responder marca el mensaje y envia el correo', function () {
    Mail::fake();
    $usuario = agenteDeContacto();
    $this->actingAs($usuario);

    $contacto = Contacto::query()->create([
        'name' => 'Prospecto',
        'email' => 'prospecto@example.com',
        'message' => 'Hola',
    ]);

    $this->post(route('blog.contactos.store'), [
        'id' => $contacto->id,
        'estado' => EstadoContacto::Respondido->value,
        'respuesta' => 'Con gusto, te comparto una propuesta.',
    ])->assertRedirect();

    $contacto->refresh();

    expect($contacto->estado)->toBe(EstadoContacto::Respondido)
        ->and($contacto->respondido_por)->toBe($usuario->id)
        ->and($contacto->respondido_at)->not->toBeNull();

    Mail::assertQueued(RespuestaContacto::class);
});

test('cambiar de estado sin respuesta no manda correo', function () {
    Mail::fake();
    $this->actingAs(agenteDeContacto());

    $contacto = Contacto::query()->create([
        'name' => 'Prospecto',
        'email' => 'prospecto@example.com',
        'message' => 'Hola',
    ]);

    $this->post(route('blog.contactos.store'), [
        'id' => $contacto->id,
        'estado' => EstadoContacto::Archivado->value,
    ])->assertRedirect();

    expect($contacto->fresh()->estado)->toBe(EstadoContacto::Archivado);

    Mail::assertNotQueued(RespuestaContacto::class);
});
