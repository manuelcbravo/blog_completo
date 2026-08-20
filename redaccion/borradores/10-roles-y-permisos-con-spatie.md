---
titulo: Roles y permisos en Laravel con spatie/laravel-permission
slug: roles-y-permisos-en-laravel-con-spatie
tipo: tutorial
estado: borrador
categoria: Seguridad
etiquetas: [laravel, seguridad, roles, permisos, spatie]
resumen: 'El paquete está en los trece proyectos que arranqué este año. Lo que marca la diferencia no es instalarlo: es que los permisos vivan en un enum y no en cadenas sueltas por todo el código.'
meta_descripcion: 'Tutorial de spatie/laravel-permission en Laravel: instalación, permisos como enum, seeder que se sincroniza solo, protección de rutas y del frontend con Inertia.'
hace_dias: 68
---

`spatie/laravel-permission` está en los trece proyectos Laravel que arranqué
este año. Es de los pocos paquetes que instalo sin evaluar alternativas.

Pero instalarlo es la parte trivial. Lo que separa una implementación que
aguanta dos años de una que se pudre es dónde vive el catálogo de permisos.

## Instalación

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Y en el modelo `User`:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

Eso crea cinco tablas: `roles`, `permissions`, y las tres pivote que las unen
con los usuarios. A partir de aquí ya funciona `$user->can('lo-que-sea')`.

## El error de arrancar con cadenas

Lo natural es escribir el permiso donde lo necesitas:

```php
Route::middleware('can:productos.gestionar')->group(/* ... */);

// y en otro archivo
if ($user->can('productos.gestionar')) { /* ... */ }

// y en el frontend
{puede('productos.gestionar') && <Boton />}
```

Funciona. Y a los seis meses tienes la cadena `productos.gestionar` repetida en
catorce archivos, más una variante `productos.gestion` que alguien escribió mal
y que nunca concede nada porque el permiso no existe.

Lo peor: **fallar mal es silencioso**. Un permiso mal escrito no lanza error,
simplemente devuelve `false`. El botón no aparece y nadie sabe por qué.

## Los permisos como enum

La solución que uso en todos los proyectos: un enum de PHP como fuente de verdad.

```php
// app/Enums/Permiso.php
namespace App\Enums;

/**
 * Catálogo de permisos (formato modulo.accion).
 *
 * `ver` es consulta; `gestionar` incluye alta, edición y baja: los
 * controladores operan por upsert, así que no tiene sentido separar
 * crear de editar.
 *
 * El RoleSeeder crea automáticamente cada caso que se agregue aquí.
 */
enum Permiso: string
{
    case UsuariosGestionar = 'usuarios.gestionar';
    case RolesGestionar = 'roles.gestionar';

    case ProductosVer = 'productos.ver';
    case ProductosGestionar = 'productos.gestionar';

    public function label(): string
    {
        return match ($this) {
            self::UsuariosGestionar => 'Gestionar usuarios',
            self::RolesGestionar => 'Gestionar roles y permisos',
            self::ProductosVer => 'Consultar productos',
            self::ProductosGestionar => 'Gestionar productos',
        };
    }
}
```

Tres cosas cambian de golpe.

**Escribir mal deja de compilar.** `Permiso::ProductosGestionar` mal tecleado es
un error de PHP, no un `false` silencioso.

**El editor te autocompleta el catálogo.** No hay que ir a buscar cómo se
llamaba el permiso.

**El `match` obliga a etiquetar.** Un `match` sin caso por omisión falla si
agregas un caso y olvidas su etiqueta. Es imposible tener un permiso sin nombre
legible en el panel de administración.

La convención `modulo.accion` con sólo `ver` y `gestionar` es deliberada. El
CRUD típico —crear, leer, actualizar, borrar— genera cuatro permisos por módulo
que nadie asigna por separado en la vida real: quien puede crear un producto
puede editarlo. Con dos niveles, un sistema de veinte módulos tiene cuarenta
permisos en vez de ochenta, y la pantalla de asignación se puede leer.

Donde sí desgloso es en la operación diaria, cuando los papeles son de verdad
distintos. En una central de taxis que hice, un checador puede formar taxis en
la fila pero no despachar un servicio ni cancelarlo. Ahí hay tres permisos, no
uno, porque hay tres personas distintas.

## El seeder que se sincroniza solo

El enum manda; la base lo sigue.

```php
// database/seeders/RoleSeeder.php
namespace Database\Seeders;

use App\Enums\Permiso;
use App\Enums\Rol;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permiso::cases() as $permiso) {
            Permission::findOrCreate($permiso->value, 'web');
        }

        $admin = Role::findOrCreate(Rol::Administrador->value, 'web');
        $admin->syncPermissions(Permission::all());

        $editor = Role::findOrCreate(Rol::Editor->value, 'web');
        $editor->syncPermissions([
            Permiso::ProductosVer->value,
            Permiso::ProductosGestionar->value,
        ]);
    }
}
```

Agregas un caso al enum, corres `php artisan db:seed --class=RoleSeeder`, y el
permiso existe en todos los entornos. No hay que acordarse de crearlo a mano en
producción.

**El `forgetCachedPermissions()` de la primera línea no es opcional.** El paquete
cachea los permisos por 24 horas para no consultarlos en cada petición. Sin esa
llamada, el seeder crea el permiso, la caché sigue con la lista vieja y pasas
media hora buscando por qué un permiso que existe en la base no concede nada.
Es el error número uno con este paquete, por mucho.

En producción, después de desplegar:

```bash
php artisan permission:cache-reset
```

## Proteger el backend

En las rutas, que es donde de verdad importa:

```php
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('can:productos.gestionar')->group(function () {
        Route::get('productos', [ProductoController::class, 'index']);
        Route::post('productos', [ProductoController::class, 'store']);
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy']);
    });
});
```

O en el Form Request, cuando la regla depende del registro:

```php
public function authorize(): bool
{
    return $this->user()?->can(Permiso::ProductosGestionar->value) ?? false;
}
```

Ese `?? false` importa. `$this->user()?->can(...)` devuelve `null` cuando no hay
usuario, y `null` no es `true`, pero tampoco es explícito. **Escribe el caso sin
usuario a mano.** Me encontré exactamente este patrón invertido en un controlador
propio —`if ($request->user()?->cannot(...))`— donde un invitado obtenía `null`,
que es falsy, y se saltaba el guardia entero. Sólo el middleware `auth` evitaba
que fuera explotable.

## El frontend

El frontend nunca decide permisos, sólo evita mostrar botones que van a fallar.
Los permisos del usuario se comparten desde el middleware de Inertia:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user(),
            'permisos' => $request->user()?->getAllPermissions()->pluck('name') ?? [],
        ],
    ]);
}
```

Y en React:

```tsx
const { auth } = usePage().props;
const puede = (permiso: string) => auth.permisos.includes(permiso);

{puede('productos.gestionar') && <BotonNuevo />}
```

Que quede claro: **esto es cosmética**. Si alguien manda el `POST` a mano, lo
que lo detiene es el middleware del backend. Ocultar el botón es para que la
interfaz no ofrezca lo que va a rechazar.

## Un súper administrador que no dependa de la lista

Siempre dejo una salida para que nadie se quede fuera de su propio sistema por
haber desmarcado la casilla equivocada:

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    Gate::before(function ($user) {
        return $user->es_super_admin ? true : null;
    });
}
```

El `null` es la parte fina: devolver `null` significa «no opino, sigue
evaluando», mientras que `false` cortaría de tajo toda la cadena de
autorización y rompería cualquier otra política. Sólo `true` corta.

## Sobre el rendimiento

`getAllPermissions()` en cada petición es una consulta con dos joins. Con la
caché del paquete encendida —que lo está por omisión— eso se resuelve en
memoria.

Lo que sí evito es `$user->hasPermissionTo()` dentro de un bucle sobre una
colección: son N evaluaciones. Se resuelve sacando la comprobación fuera:

```php
$puedeGestionar = $user->can(Permiso::ProductosGestionar->value);

foreach ($productos as $producto) {
    // usar $puedeGestionar
}
```

Obvio dicho así, y de todas formas es de las cosas que más veo en revisiones de
código.
