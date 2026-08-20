<?php

namespace Database\Seeders;

use App\Enums\EstadoComentario;
use App\Enums\EstadoPublicacion;
use App\Enums\EstadoSuscriptor;
use App\Enums\TipoPublicacion;
use App\Models\Categoria;
use App\Models\Comentario;
use App\Models\Contacto;
use App\Models\Etiqueta;
use App\Models\Suscriptor;
use App\Models\User;
use App\Models\Vista;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $autor = User::query()->orderBy('id')->firstOrFail();

        $categorias = collect(['Arquitectura', 'Colas', 'Eloquent', 'Testing', 'Deploy'])
            ->map(fn (string $nombre) => Categoria::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['slug' => Str::slug($nombre), 'descripcion' => "Publicaciones sobre {$nombre}."],
            ));

        $etiquetas = collect(['php', 'inertia', 'postgres', 'docker', 'typescript'])
            ->map(fn (string $nombre) => Etiqueta::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['slug' => Str::slug($nombre)],
            ));

        $resumenes = [
            'Dejamos de usar microservicios y nadie lo notó' => 'Doce servicios, tres colas y un equipo de seis personas. Cómo volvimos a un Laravel modular y cuánto bajó la factura.',
            'Colas en Laravel: lo que nadie te cuenta de los reintentos' => 'Backoff, jobs idempotentes y el día que un correo se envió cuatro mil veces.',
            'Eloquent no es lento, tus consultas sí' => 'Cuatro problemas que veo en cada auditoría y cómo se arreglan sin cambiar de ORM.',
            'Tests que sí ejecutas: Pest en un proyecto real' => 'De 40 minutos de suite a 90 segundos sin borrar un solo test.',
            'Deploy sin downtime con un VPS de 12 €' => 'Enlaces simbólicos, healthcheck y un script de 30 líneas.',
            'Panel de control con Inertia y React' => 'Cómo quedó el panel: tablas, diálogos y un editor de contenido que no estorba.',
        ];

        $contenidos = [
            [TipoPublicacion::Post, [
                ['Dejamos de usar microservicios y nadie lo notó', EstadoPublicacion::Publicado],
                ['Colas en Laravel: lo que nadie te cuenta de los reintentos', EstadoPublicacion::Publicado],
                ['Eloquent no es lento, tus consultas sí', EstadoPublicacion::Publicado],
                ['Tests que sí ejecutas: Pest en un proyecto real', EstadoPublicacion::Publicado],
                ['Deploy sin downtime con un VPS de 12 €', EstadoPublicacion::Publicado],
                ['Panel de control con Inertia y React', EstadoPublicacion::Publicado],
                ['Migrar de Livewire a Inertia sin morir en el intento', EstadoPublicacion::Borrador],
            ]],
            [TipoPublicacion::Tutorial, [
                ['Instalar Postgres en Laragon paso a paso', EstadoPublicacion::Publicado],
                ['Colas de Laravel en producción', EstadoPublicacion::Revision],
            ]],
            [TipoPublicacion::Recurso, [
                ['Plantilla base Laravel 13 + React', EstadoPublicacion::Publicado],
                ['Checklist de despliegue', EstadoPublicacion::Borrador],
            ]],
        ];

        foreach ($contenidos as [$tipo, $filas]) {
            foreach ($filas as [$titulo, $estado]) {
                $modelo = $tipo->modelo();

                $publicacion = $modelo::query()->firstOrCreate(
                    ['slug' => Str::slug($titulo)],
                    [
                        'titulo' => $titulo,
                        'resumen' => $resumenes[$titulo] ?? 'Notas y aprendizajes sobre '.mb_strtolower($titulo).'.',
                        'contenido' => '<p>'.str_repeat('Contenido de ejemplo para la publicación. ', 40).'</p>',
                        'estado' => $estado,
                        'fecha_publicacion' => $estado === EstadoPublicacion::Publicado ? now()->subDays(random_int(1, 25)) : null,
                        'tags_seo' => 'laravel, react, desarrollo',
                        'id_categoria' => $categorias->random()->id,
                        'id_autor' => $autor->id,
                        'meta_titulo' => $titulo,
                        'meta_descripcion' => 'Guía práctica sobre '.mb_strtolower($titulo).'.',
                        'importante' => random_int(0, 1) === 1,
                    ],
                );

                $publicacion->etiquetas()->sync(
                    $etiquetas->random(2)->pluck('id')->all(),
                );

                if ($estado !== EstadoPublicacion::Publicado) {
                    continue;
                }

                $lecturas = random_int(10, 60);

                for ($i = 0; $i < $lecturas; $i++) {
                    Vista::query()->create([
                        'post_id' => $publicacion->id,
                        'tipo' => $tipo->value,
                        'ip_address' => '127.0.0.'.random_int(1, 250),
                        'session_id' => Str::random(20),
                        'created_at' => now()->subDays(random_int(0, 29)),
                        'updated_at' => now(),
                    ]);
                }

                $publicacion->forceFill(['visitas' => $lecturas])->save();

                Comentario::query()->create([
                    'post_id' => $publicacion->id,
                    'tipo' => $tipo->value,
                    'nombre' => 'Visitante '.random_int(1, 99),
                    'correo' => 'visitante'.random_int(1, 99).'@example.com',
                    'contenido' => 'Muy claro el artículo, ¿tienes el repositorio publicado?',
                    'estado' => EstadoComentario::Pendiente,
                ]);
            }
        }

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
