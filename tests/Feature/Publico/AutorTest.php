<?php

use App\Enums\EstadoContacto;
use App\Mail\AcuseContacto;
use App\Mail\NuevoContacto;
use App\Models\Contacto;
use Illuminate\Support\Facades\Mail;

test('la ficha del autor trae todo el contenido de la hoja de vida', function () {
    $ficha = config('autor');

    $respuesta = $this->get(route('publico.autor'))->assertOk();

    $respuesta->assertSee($ficha['nombre'].'<br>'.$ficha['apellidos'], false)
        ->assertSee($ficha['titulo'])
        ->assertSee($ficha['ubicacion'])
        ->assertSee($ficha['resumen'], false);

    $respuesta->assertSee($ficha['titular'])
        ->assertSee($ficha['experiencia'])
        ->assertSee($ficha['modalidad'])
        ->assertSee($ficha['telefono']);

    foreach ($ficha['trayectoria'] as $puesto) {
        $respuesta->assertSee($puesto['puesto'])
            ->assertSee($puesto['periodo'])
            ->assertSee($puesto['empresa'])
            ->assertSee($puesto['lugar'])
            ->assertSee($puesto['resumen'], false);

        foreach ($puesto['logros'] as $logro) {
            $respuesta->assertSee($logro, false);
        }
    }

    foreach ($ficha['educacion'] as $estudio) {
        $respuesta->assertSee($estudio['titulo'])->assertSee($estudio['institucion']);
    }

    foreach ($ficha['certificaciones'] as $certificacion) {
        $respuesta->assertSee($certificacion['nombre'])->assertSee($certificacion['detalle']);
    }

    foreach ($ficha['aptitudes'] as $grupo) {
        $respuesta->assertSee($grupo['grupo']);

        foreach ($grupo['items'] as $item) {
            $respuesta->assertSee($item);
        }
    }

    $respuesta->assertSee($ficha['idiomas']);

    foreach ($ficha['habilidades'] as $habilidad) {
        $respuesta->assertSee($habilidad);
    }

    $respuesta->assertSee('¿Tienes un proyecto?');
});

test('el formulario de contacto del autor guarda y avisa', function () {
    Mail::fake();
    config()->set('blog.admin_email', 'admin@example.com');

    $this->post(route('publico.contactar'), [
        'nombre' => 'Prospecto',
        'email' => 'prospecto@example.com',
        'mensaje' => 'Quiero un ERP parecido al que describes en el blog.',
    ])->assertRedirect();

    $contacto = Contacto::query()->firstOrFail();

    expect($contacto->name)->toBe('Prospecto')
        ->and($contacto->estado)->toBe(EstadoContacto::Nuevo)
        ->and($contacto->ip_address)->not->toBeNull();

    Mail::assertQueued(NuevoContacto::class);
    Mail::assertQueued(AcuseContacto::class);
});

test('el honeypot corta el formulario de contacto', function () {
    Mail::fake();

    $this->post(route('publico.contactar'), [
        'nombre' => 'Bot',
        'email' => 'bot@example.com',
        'mensaje' => 'spam spam spam spam spam',
        'sitio_web' => 'http://spam.example',
    ])->assertSessionHasErrors('sitio_web');

    expect(Contacto::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});
