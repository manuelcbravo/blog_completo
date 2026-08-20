<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class MakeModulo extends Command
{
    /**
     * @var string
     */
    protected $signature = 'make:modulo
        {nombre : Nombre del modelo en singular (ej. Producto)}
        {--grupo= : Grupo que agrupa el modulo y prefija sus rutas (ej. config)}
        {--plural= : Plural del recurso cuando Str::plural no acierta (ej. Roles)}
        {--permiso= : Permiso que protege las rutas (por defecto <recurso>.gestionar)}
        {--force : Sobrescribe los archivos que ya existan}';

    /**
     * @var string
     */
    protected $description = 'Genera un modulo CRUD completo (modelo, migracion, controlador, request, resource y pagina Inertia)';

    public function handle(): int
    {
        $nombre = Str::studly((string) $this->argument('nombre'));

        if ($nombre === '') {
            $this->components->error('El nombre del modulo no puede estar vacio.');

            return self::FAILURE;
        }

        $grupoOpcion = $this->option('grupo');
        $grupo = is_string($grupoOpcion) && $grupoOpcion !== '' ? Str::studly($grupoOpcion) : null;

        $pluralOpcion = $this->option('plural');
        $plural = is_string($pluralOpcion) && $pluralOpcion !== ''
            ? Str::studly($pluralOpcion)
            : Str::plural($nombre);

        $recurso = Str::kebab($plural);
        $grupoKebab = $grupo === null ? null : Str::kebab($grupo);
        $prefijoRuta = ($grupoKebab === null ? '' : $grupoKebab.'.').$recurso;

        $permisoOpcion = $this->option('permiso');
        $permiso = is_string($permisoOpcion) && $permisoOpcion !== ''
            ? $permisoOpcion
            : $recurso.'.gestionar';

        $reemplazos = [
            '{{ modelo }}' => $nombre,
            '{{ modeloPlural }}' => $plural,
            '{{ tabla }}' => Str::snake($plural),
            '{{ recurso }}' => $recurso,
            '{{ variable }}' => Str::camel($nombre),
            '{{ variablePlural }}' => Str::camel($plural),
            '{{ propPlural }}' => Str::camel($plural),
            '{{ namespaceGrupo }}' => $grupo === null ? '' : '\\'.$grupo,
            '{{ rutaPagina }}' => ($grupoKebab === null ? '' : $grupoKebab.'/').$recurso.'/index',
            '{{ rutaWayfinder }}' => ($grupoKebab === null ? '' : $grupoKebab.'/').$recurso,
            '{{ etiqueta }}' => $nombre,
            '{{ etiquetaPlural }}' => $plural,
            '{{ etiquetaMinuscula }}' => Str::lower($nombre),
        ];

        $subcarpeta = $grupo === null ? '' : $grupo.'/';
        $subcarpetaPagina = $grupoKebab === null ? '' : $grupoKebab.'/';

        $tabla = $reemplazos['{{ tabla }}'];

        // La migracion lleva timestamp en el nombre: si ya existe una para esta
        // tabla hay que reusar su ruta, o cada corrida crearia un duplicado.
        $migracion = File::glob(base_path("database/migrations/*_create_{$tabla}_table.php"))[0]
            ?? base_path('database/migrations/'.date('Y_m_d_His')."_create_{$tabla}_table.php");

        $archivos = [
            'model' => base_path("app/Models/{$nombre}.php"),
            'factory' => base_path("database/factories/{$nombre}Factory.php"),
            'migration' => $migracion,
            'controller' => base_path("app/Http/Controllers/{$subcarpeta}{$nombre}Controller.php"),
            'request' => base_path("app/Http/Requests/{$subcarpeta}Upsert{$nombre}Request.php"),
            'resource' => base_path("app/Http/Resources/{$subcarpeta}{$nombre}Resource.php"),
            'page' => base_path("resources/js/pages/{$subcarpetaPagina}{$recurso}/index.tsx"),
        ];

        $forzar = (bool) $this->option('force');
        $generados = 0;

        foreach ($archivos as $stub => $destino) {
            $origen = base_path("stubs/modulo/{$stub}.stub");

            if (! File::exists($origen)) {
                $this->components->error("No se encontro el stub {$stub}.stub.");

                return self::FAILURE;
            }

            if (File::exists($destino) && ! $forzar) {
                $this->components->warn('Ya existe, se omite: '.$this->rutaRelativa($destino));

                continue;
            }

            File::ensureDirectoryExists(dirname($destino));
            File::put($destino, str_replace(
                array_keys($reemplazos),
                array_values($reemplazos),
                File::get($origen),
            ));

            $this->components->info('Creado: '.$this->rutaRelativa($destino));
            $generados++;
        }

        if ($generados === 0) {
            $this->components->warn('No se genero ningun archivo. Usa --force para sobrescribir.');

            return self::SUCCESS;
        }

        $this->formatearPagina($archivos['page']);
        $this->siguientesPasos($nombre, $recurso, $grupoKebab, $prefijoRuta, $permiso, $reemplazos['{{ variable }}']);

        return self::SUCCESS;
    }

    /**
     * El stub es estatico, asi que el ancho de las lineas depende del largo del
     * nombre sustituido; Prettier deja la pagina como el resto del proyecto.
     */
    protected function formatearPagina(string $ruta): void
    {
        if (! File::exists($ruta)) {
            return;
        }

        $relativa = $this->rutaRelativa($ruta);
        $binario = base_path('node_modules/.bin/prettier'.(PHP_OS_FAMILY === 'Windows' ? '.cmd' : ''));

        if (! File::exists($binario)) {
            $this->components->warn('Prettier no esta instalado; corre "npm run format" a mano.');

            return;
        }

        $resultado = Process::path(base_path())->timeout(120)->run([$binario, '--write', $relativa]);

        if ($resultado->successful()) {
            $this->components->info('Formateado con Prettier: '.$relativa);

            return;
        }

        $this->components->warn('Prettier fallo; corre "npm run format" a mano.');
    }

    protected function rutaRelativa(string $ruta): string
    {
        return str_replace('\\', '/', Str::after($ruta, base_path().DIRECTORY_SEPARATOR));
    }

    protected function siguientesPasos(
        string $nombre,
        string $recurso,
        ?string $grupoKebab,
        string $prefijoRuta,
        string $permiso,
        string $variable,
    ): void {
        $namespace = $grupoKebab === null
            ? "App\\Http\\Controllers\\{$nombre}Controller"
            : 'App\\Http\\Controllers\\'.Str::studly($grupoKebab)."\\{$nombre}Controller";

        $rutas = [];
        $rutas[] = "use {$namespace};";
        $rutas[] = '';

        if ($grupoKebab !== null) {
            $rutas[] = "Route::prefix('{$grupoKebab}')->middleware('can:{$permiso}')->group(function () {";
            $sangria = '    ';
        } else {
            $rutas[] = "Route::middleware('can:{$permiso}')->group(function () {";
            $sangria = '    ';
        }

        $rutas[] = $sangria."Route::get('{$recurso}', [{$nombre}Controller::class, 'index'])->name('{$prefijoRuta}.index');";
        $rutas[] = $sangria."Route::post('{$recurso}', [{$nombre}Controller::class, 'store'])->name('{$prefijoRuta}.store');";
        $rutas[] = $sangria."Route::delete('{$recurso}/{{$variable}}', [{$nombre}Controller::class, 'destroy'])->name('{$prefijoRuta}.destroy');";
        $rutas[] = '});';

        $this->newLine();
        $this->components->info('Siguientes pasos');

        $this->line('  <fg=gray>1.</> Agrega el permiso en <fg=yellow>app/Enums/Permiso.php</>:');
        $this->newLine();
        $this->line('     case '.Str::studly(str_replace('.', ' ', $permiso))." = '{$permiso}';");
        $this->newLine();

        $this->line('  <fg=gray>2.</> Agrega las rutas en <fg=yellow>routes/web.php</> dentro del grupo autenticado:');
        $this->newLine();

        foreach ($rutas as $linea) {
            $this->line($linea === '' ? '' : '     '.$linea);
        }

        $this->newLine();
        $this->line('  <fg=gray>3.</> Corre <fg=yellow>php artisan migrate</> y <fg=yellow>php artisan db:seed --class=RoleSeeder</>.');
        $this->line('  <fg=gray>4.</> Corre <fg=yellow>npm run dev</> para que Wayfinder genere <fg=yellow>@/routes/'.($grupoKebab === null ? '' : $grupoKebab.'/').$recurso.'</>.');
        $this->newLine();
    }
}
