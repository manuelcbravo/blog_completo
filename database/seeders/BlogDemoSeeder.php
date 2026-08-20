<?php

namespace Database\Seeders;

use App\Enums\EstadoSuscriptor;
use App\Models\Contacto;
use App\Models\Suscriptor;
use Illuminate\Database\Seeder;

/**
 * Datos de relleno para desarrollo: suscriptores y mensajes de contacto, para
 * poder ver esas pantallas con algo dentro.
 *
 * Ya no siembra publicaciones. Las publicaciones reales viven en
 * redaccion/borradores y las siembra BlogContenidoSeeder; tener aquí artículos
 * inventados sólo servía para que se colaran al sitio público.
 *
 *     php artisan db:seed --class=BlogDemoSeeder
 */
class BlogDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 12) as $i) {
            Suscriptor::query()->firstOrCreate(
                ['email' => "suscriptor{$i}@example.com"],
                [
                    'nombre' => "Suscriptor {$i}",
                    'estado' => $i % 4 === 0 ? EstadoSuscriptor::Pendiente : EstadoSuscriptor::Confirmado,
                    'token' => Suscriptor::nuevoToken(),
                    'origen' => 'demo',
                    'confirmado_at' => $i % 4 === 0 ? null : now()->subDays($i),
                    'created_at' => now()->subDays($i),
                ],
            );
        }

        foreach (range(1, 4) as $i) {
            Contacto::query()->firstOrCreate(
                ['email' => "contacto{$i}@example.com"],
                [
                    'name' => "Contacto {$i}",
                    'message' => 'Hola, me interesa una cotización para un proyecto parecido al que publicaste.',
                    'ip_address' => '127.0.0.1',
                    'created_at' => now()->subDays($i),
                ],
            );
        }
    }
}
