<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cuenta de demostración: la que se enseña en la página de proyectos para que
 * cualquiera recorra el panel sin pedir acceso.
 *
 * Sus credenciales salen del `.env` —las mismas que muestra la vitrina—, así
 * que no hay ninguna contraseña escrita en el repositorio. Sin
 * `DEMO_ACCESO_CLAVE` la cuenta no se crea: es el interruptor para apagar la
 * demostración de un solo movimiento.
 *
 * Sus permisos los define Rol::Demo, no este seeder. Aquí sólo se asigna el
 * rol, para que la lista de lo que puede hacer viva en un único lugar.
 */
class UsuarioDemoSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('plataforma.demo.email');
        $password = config('plataforma.demo.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        $demo = User::query()->firstOrNew(['email' => $email]);

        $demo->forceFill([
            'name' => (string) config('plataforma.demo.nombre'),
            'password' => Hash::make($password),
            'es_super_admin' => false,
            'email_verified_at' => $demo->email_verified_at ?? now(),
        ])->save();

        // syncRoles y no assignRole: si alguien le agregó otro rol a mano en el
        // panel, sembrar de nuevo lo devuelve a ser sólo demo.
        $demo->syncRoles([Rol::Demo->value]);
    }
}
