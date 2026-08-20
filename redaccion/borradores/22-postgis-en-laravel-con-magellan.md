---
titulo: PostGIS en Laravel sin escribir SQL a mano
slug: postgis-en-laravel-con-magellan
tipo: tutorial
estado: borrador
categoria: Geoespacial
etiquetas: [laravel, postgis, postgresql, eloquent, mapas]
resumen: clickbar/laravel-magellan mete los tipos espaciales en las migraciones y en Eloquent. Con un detalle que aprendí de un municipio con forma de herradura y que ningún tutorial menciona.
meta_descripcion: Usar PostGIS desde Laravel con clickbar/laravel-magellan, tipos espaciales en migraciones, consultas desde Eloquent y la diferencia entre ST_Centroid y ST_PointOnSurface.
hace_dias: 57
---

Trabajar con PostGIS desde Laravel tiene un problema tonto: el constructor de
esquemas no conoce los tipos espaciales, así que las migraciones acaban llenas
de `DB::statement()` con SQL crudo, y los modelos devuelven las geometrías como
cadenas ilegibles.

`clickbar/laravel-magellan` resuelve las dos cosas.

## Instalación

```bash
composer require clickbar/laravel-magellan
```

Y una migración que habilite la extensión antes que nada:

```php
public function up(): void
{
    DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
}
```

Esa migración va **primero**, con un nombre que garantice el orden. Yo la llamo
`0000_00_00_000000_habilitar_postgis.php`: los ceros la mandan al inicio, antes
de cualquier tabla con columna espacial.

## Migraciones legibles

Con el paquete, los tipos espaciales son métodos del `Blueprint`:

```php
Schema::create('municipios', function (Blueprint $table) {
    $table->id();
    $table->unsignedSmallInteger('clave_entidad');
    $table->unsignedSmallInteger('clave_municipio');
    $table->string('nombre', 120);
    $table->string('nombre_busqueda', 120);

    $table->magellanMultiPolygon('geom', 4326);
    $table->magellanPoint('centroide', 4326);

    $table->timestamps();

    $table->unique(['clave_entidad', 'clave_municipio']);
    $table->index('nombre_busqueda');
});
```

`magellanPoint`, `magellanMultiPolygon`, `magellanLineString` y compañía, todos
con su SRID como segundo argumento. Se lee como una migración normal.

Dos cosas de esa tabla que no son del paquete pero valen:

**`nombre_busqueda`**, una copia normalizada en mayúsculas y sin acentos. Cuando
alguien teclea «tepeji» quieres encontrar «Tepejí del Río de Ocampo», y eso no
sale de un `LIKE` sobre el nombre con acentos.

**La clave de entidad en la tabla y en el índice único** aunque hoy sólo se cargue
un estado. Es lo que hace que cargar otro sea sembrar filas y no migrar nada.

## El detalle del centroide

Aquí está lo que aprendí por las malas.

Cada municipio guarda un `centroide` que se usa como destino por omisión cuando
alguien elige ese municipio en la interfaz. Lo natural es calcularlo con
`ST_Centroid`.

**`ST_Centroid` puede devolver un punto fuera del polígono.**

Es el centro de masa. En un municipio con forma de herradura, de media luna o con
un enclave de otro municipio en medio —y en México hay varios—, ese centro cae en
el hueco. Tu «centro del municipio» termina siendo un punto en el municipio
vecino.

La función correcta es:

```sql
ST_PointOnSurface(geom)
```

**Garantiza un punto dentro del polígono.** No es el centro geométrico y no
importa: lo que necesitas es un punto que represente al municipio y que esté
dentro de él.

Lo mismo aplica a colonias con forma irregular, que son la mayoría de las que
crecieron sin planeación.

Es de esos errores que no fallan: producen un dato plausible y equivocado, y sólo
se descubren cuando alguien mira el mapa y dice «ese punto no está ahí».

## Consultar desde Eloquent

El paquete agrega los operadores espaciales al constructor de consultas:

```php
use Clickbar\Magellan\Database\MagellanBaseExpression as ST;

$cercanas = Colonia::query()
    ->whereST('geom', ST::contains(ST::point($lon, $lat, 4326)))
    ->get();
```

Y las geometrías llegan como objetos, no como cadenas WKB:

```php
$municipio->centroide->getLatitude();
$municipio->centroide->getLongitude();
```

Para lo que hago —geocodificación inversa, radios de cobertura, zonas de
tarifa— eso cubre casi todo.

## Cuándo sigo bajando a SQL

Sin dogmas: cuando la consulta espacial es el corazón de la operación, la escribo
en SQL.

En la central de taxis hay una consulta que, dado un punto de origen, resuelve la
zona de tarifa, calcula la distancia a la terminal y ordena por cercanía, todo en
una pasada. Expresarla con el constructor la vuelve ilegible y no gana nada: es
una consulta que se escribe una vez y se lee muchas.

La línea que uso: **el paquete para el esquema y para las consultas simples; SQL
crudo para la lógica espacial de negocio.** Y ese SQL en una función de PostgreSQL
o en un método con nombre, no incrustado en un controlador.

## Las zonas circulares

Un uso que resultó más útil de lo esperado: las terminales tienen un radio de
cobertura, y se calcula con un buffer sobre `geography`:

```sql
ST_Buffer(ubicacion::geography, 800)   -- 800 metros
```

Sobre `geography` el radio va en metros. Sobre `geometry` con SRID 4326 iría en
grados, que es el error del que ya he escrito antes y que se paga caro.

Con eso, «¿esta dirección está dentro de la cobertura de alguna terminal?» es un
`ST_Contains` contra el buffer, y cambiar la cobertura de una terminal es
actualizar un número, no redibujar un polígono a mano.

## Si vas a empezar

Tres cosas, en este orden:

**La migración de la extensión, primero y con nombre que lo garantice.** Una
migración espacial que corre antes que `CREATE EXTENSION postgis` falla, y el
mensaje de error no es obvio.

**Decide `geography` o `geometry` por tabla, no por proyecto.** Los datos que
comparas por distancia en metros van en `geography`. La cartografía grande que
sólo se consulta por contención puede ir en `geometry` y es más rápida.

**Índices GIST desde la primera migración.** `$table->index('geom')` con el
paquete crea el GIST correcto. Agregarlo después, cuando la tabla ya tiene
cincuenta mil polígonos, es una operación que bloquea.
