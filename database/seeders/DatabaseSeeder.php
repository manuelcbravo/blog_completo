<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Deja la plataforma lista de punta a punta: permisos, roles, el super
 * administrador, la cuenta de demostración y el contenido real del blog.
 *
 *     php artisan db:seed
 *
 * Es idempotente. El contenido sale de redaccion/borradores, así que sembrar
 * dos veces actualiza lo que cambió en vez de duplicarlo.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = User::query()->where('email', 'admin@fielgroup.com.mx')->first()
            ?? User::factory()->create([
                'name' => 'Administrador',
                'email' => 'admin@fielgroup.com.mx',
                'password' => $this->passwordAdmin(),
            ]);

        $admin->forceFill(['es_super_admin' => true])->save();

        $this->call(UsuarioDemoSeeder::class);
        $this->call(BlogContenidoSeeder::class);
    }

    /**
     * En producción la contraseña DEBE venir del entorno; el valor por
     * defecto débil existe solo para desarrollo local.
     */
    private function passwordAdmin(): string
    {
        $password = config('plataforma.admin_seed_password');

        if (is_string($password) && $password !== '') {
            return $password;
        }

        if (app()->isProduction()) {
            throw new RuntimeException('Define ADMIN_SEED_PASSWORD en el .env para sembrar en producción.');
        }

        return '12345678';
    }
}
