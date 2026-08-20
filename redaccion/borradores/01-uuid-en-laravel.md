---
titulo: 'UUID en Laravel: por qué usarlos y qué beneficios traen'
slug: uuid-en-laravel-por-que-usarlos
tipo: post
estado: borrador
categoria: Base de datos
etiquetas: [laravel, postgresql, api, seguridad]
resumen: Un id autoincremental le dice a cualquiera cuántos clientes tienes y qué tan rápido creces. Cómo agregué UUID a tablas que ya estaban en producción, sin tocar la llave primaria.
meta_descripcion: Por qué usar UUID en Laravel como identificador público, cómo agregarlos a una tabla que ya tiene datos y por qué conviene generarlos en PostgreSQL y no en PHP.
hace_dias: 4
importante: true
---

El primer cliente de una plataforma que hice tenía la URL `/empresas/1`. El
segundo, `/empresas/2`. Cuando llegamos al catorce, cualquiera que entrara a su
propio panel podía cambiar un número en la barra de direcciones y saber
exactamente cuántas empresas había dentro del sistema.

No se filtró nada: el control de acceso hacía su trabajo y devolvía un 403.
Pero el número ya había dicho lo suyo. Un `id` autoincremental en una URL es
una fuga de información de negocio, y es gratis: no hace falta explotar nada,
sólo saber contar.

## Lo que no hay que hacer

La reacción típica es cambiar la llave primaria a UUID. Yo no lo hago, y creo
que en la mayoría de las aplicaciones es un error.

Un UUID versión 4 es aleatorio. Como llave primaria en una tabla InnoDB o en un
índice agrupado, eso significa que cada inserción cae en una página distinta del
índice en lugar de al final. El índice se fragmenta, las páginas se parten, y
la tabla crece más de lo que debería. Además pasas de 8 bytes por llave a 16, y
ese costo se multiplica en cada llave foránea y en cada índice que la incluya.

Para una tabla de cien mil filas da igual. Para la tabla de movimientos de
inventario de un punto de venta, no.

## Lo que sí hago: dos identificadores, dos trabajos

**El `id` se queda como llave primaria** y sigue siendo entero autoincremental.
Es interno: vive en las llaves foráneas, en los `join`, en los índices. Nunca
sale a una URL, a una API pública ni a un QR.

**El UUID es el identificador público.** Es el que va en la ruta, en el enlace
que se manda por WhatsApp, en el código impreso. Es opaco: no revela orden ni
volumen, y no se puede adivinar el siguiente.

Cuesta una columna y un índice único. A cambio, las URLs dejan de contar tu
negocio.

## Agregarlo a una tabla que ya tiene datos

Aquí está el problema real. Poner `$table->uuid('uuid')->unique()` en una tabla
vacía es trivial. Hacerlo en una que ya está en producción, con datos, y
terminar con una columna `NOT NULL` sin que la migración explote, requiere tres
pasos.

Así lo hice en la tabla de empresas de una plataforma de avalúos:

```php
// database/migrations/..._add_id_uuid_to_empresas_table.php
public function up(): void
{
    Schema::table('empresas', function (Blueprint $table) {
        $table->uuid('id_uuid')->nullable()->unique()->after('id');
    });

    DB::table('empresas')->orderBy('id')->each(function ($empresa) {
        DB::table('empresas')
            ->where('id', $empresa->id)
            ->update(['id_uuid' => Str::uuid()->toString()]);
    });

    Schema::table('empresas', function (Blueprint $table) {
        $table->uuid('id_uuid')->nullable(false)->change();
    });
}
```

Primero la columna **nullable**, porque si naciera `NOT NULL` la migración
fallaría en la primera fila existente. Después el relleno. Sólo al final se
endurece la restricción.

El `->orderBy('id')->each()` no es decorativo: `each()` recorre en lotes de mil
por omisión, así que la migración no carga la tabla entera en memoria. En una
tabla de dos millones de filas es la diferencia entre migrar y quedarte sin RAM.

## La versión mejor: que lo genere la base

Un mes después hice lo mismo en la tabla de servicios de una central de taxis, y
lo hice distinto:

```php
// database/migrations/..._add_uuid_a_servicios.php
public function up(): void
{
    Schema::table('servicios', function (Blueprint $table) {
        $table->uuid('uuid')->nullable()->after('folio');
    });

    DB::statement('UPDATE servicios SET uuid = gen_random_uuid() WHERE uuid IS NULL');

    DB::statement('ALTER TABLE servicios ALTER COLUMN uuid SET DEFAULT gen_random_uuid()');
    DB::statement('ALTER TABLE servicios ALTER COLUMN uuid SET NOT NULL');

    Schema::table('servicios', function (Blueprint $table) {
        $table->unique('uuid');
    });
}
```

Misma estructura de tres pasos, pero el UUID lo genera PostgreSQL con
`gen_random_uuid()`, y sobre todo **queda como valor por omisión de la columna**.

Esa línea es la que importa:

```sql
ALTER TABLE servicios ALTER COLUMN uuid SET DEFAULT gen_random_uuid()
```

Con la primera versión, el UUID sólo existe si el registro se creó pasando por
el modelo de Eloquent. Un `INSERT` desde un seeder, desde `psql`, desde una
importación masiva o desde otro servicio deja la columna vacía —y con `NOT NULL`,
revienta. Con la segunda, la base lo garantiza siempre, venga de donde venga el
registro.

Es la misma razón por la que las llaves foráneas se declaran en la base y no
sólo se respetan desde el código: **la aplicación no es el único que escribe en
la tabla**.

En PostgreSQL 13 y superiores `gen_random_uuid()` viene de fábrica. En versiones
anteriores hay que habilitar `pgcrypto`. En MySQL 8 el equivalente es
`DEFAULT (UUID())`, con la advertencia de que el UUID de MySQL es versión 1 y sí
codifica marca de tiempo y dirección MAC —o sea que no es opaco, y para esto no
sirve.

## Cómo se usa desde el modelo

Basta con decirle a Laravel que resuelva las rutas por esa columna:

```php
public function getRouteKeyName(): string
{
    return 'uuid';
}
```

A partir de ahí, `Route::get('servicios/{servicio}', ...)` con enlace implícito
busca por UUID. Las relaciones internas siguen usando `id` y no se enteran de
nada.

Y si prefieres no cambiar el comportamiento global, dejas `getRouteKeyName()` en
paz y resuelves donde te convenga:

```php
Route::get('publico/servicios/{uuid}', function (string $uuid) {
    $servicio = Servicio::query()->where('uuid', $uuid)->firstOrFail();
    // ...
});
```

Yo suelo hacer esto último: rutas internas del panel por `id`, que son más
cortas y las ve sólo quien ya tiene sesión, y rutas públicas por UUID.

## Cuándo no vale la pena

No todo necesita UUID. Un catálogo de estados de la república tiene 32 filas
que no cambian nunca y que no le importan a nadie. Ponerle UUID es ceremonia.

La pregunta que uso es simple: **¿el identificador va a salir de la aplicación?**
Si aparece en una URL que alguien puede compartir, en un código impreso, en un
enlace de WhatsApp o en una respuesta de API que consume un tercero, entonces
sí. Si sólo vive entre `join`, no.

## Lo que gané

Tres cosas concretas, en orden de cuánto me han servido:

**Las URLs dejaron de ser un contador.** Nadie deduce cuántas empresas, cuántos
servicios ni a qué ritmo crecen.

**Los identificadores se pueden generar antes de insertar.** Cuando una
aplicación móvil crea un registro sin conexión y lo sincroniza después, necesita
un identificador que no dependa de la base. Con UUID, el cliente lo genera y no
hay que reconciliar nada al subir.

**Fusionar bases dejó de dar miedo.** Dos instalaciones con `id` autoincremental
tienen ambas un registro número 1. Con UUID no hay colisión, y eso convierte una
migración de un fin de semana en un `INSERT`.

Ninguna de las tres justifica cambiar la llave primaria. Las tres se consiguen
con una columna extra.
