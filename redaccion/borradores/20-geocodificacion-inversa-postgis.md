---
titulo: 'Geocodificación inversa con PostGIS: en qué colonia y en qué sección cayó ese punto'
slug: geocodificacion-inversa-con-postgis
tipo: tutorial
estado: borrador
categoria: Geoespacial
etiquetas: [postgis, postgresql, inegi, ine, sql, mapas]
resumen: Una función de veinte líneas que recibe latitud y longitud y devuelve colonia, código postal, municipio con clave INEGI, sección electoral y distrito. Sin API de terceros y sin costo por consulta.
meta_descripcion: Cómo hacer geocodificación inversa con PostGIS y ST_Contains, importar shapefiles del INE e INEGI con shp2pgsql y resolver colonia, sección y municipio desde un punto.
hace_dias: 43
importante: true
---

Alguien manda su ubicación por WhatsApp. Llegan dos números: `20.1234, -98.7654`.

Lo que necesitas es «Colonia Céntro, CP 42000, Pachuca de Soto, sección 1183,
distrito local 8». Y lo necesitas sin pagar por consulta a un servicio externo,
porque en un mes de campaña son decenas de miles.

Eso se resuelve con PostGIS y una función de veinte líneas. Este es el camino
completo.

## Lo que hace falta

**PostgreSQL con PostGIS.** En Windows se instala con el Stack Builder que viene
con PostgreSQL: Application Stack Builder → tu servidor → Spatial Extensions →
PostGIS Bundle. Eso agrega la extensión y, lo que más importa, el ejecutable
`shp2pgsql.exe`.

**Los shapefiles.** El INEGI publica el marco geoestadístico con municipios,
localidades, AGEB y manzanas. El INE publica la cartografía electoral con
secciones y distritos. Las colonias suelen venir del catálogo estatal o del
municipio.

Son archivos `.shp` con sus acompañantes `.dbf`, `.shx` y `.prj`. Hay que
importarlos.

## Importar los shapefiles, con las dos banderas que importan

```powershell
shp2pgsql -s 32614:4326 -W LATIN1 -I -D -g geom colonias.shp public.colonias | psql -d mi_base
```

Cada bandera de esa línea me costó tiempo descubrirla:

**`-s 32614:4326`** reproyecta. Los shapefiles oficiales mexicanos vienen en
**UTM zona 14N** (EPSG:32614), que mide en metros sobre una proyección plana. Tus
puntos de GPS vienen en **WGS84** (EPSG:4326), que son grados de latitud y
longitud. Si no reproyectas al importar, `ST_Contains` no encuentra nada nunca —y
no da error, simplemente devuelve vacío, que es la peor forma de fallar.

Los dos números son origen y destino. Y hay que verificar el origen en el `.prj`
del shapefile: no todos los estados publican en la misma zona UTM. México abarca
de la zona 11 a la 16.

**`-W LATIN1`** es la codificación. Los shapefiles oficiales vienen en Latin-1, no
en UTF-8. Sin esa bandera, cada «Ñ» y cada acento entra roto en la base, y lo
descubres cuando alguien busca «Peñitas» y no aparece.

**`-I`** crea el índice espacial GIST. Sin él, cada consulta es un recorrido
completo de la tabla. Con miles de polígonos, la diferencia es de milisegundos a
segundos.

**`-D`** usa el formato de volcado en lugar de `INSERT` uno por uno. En una
importación de cincuenta mil manzanas, es la diferencia entre minutos y una hora.

**`-g geom`** nombra la columna de geometría. Sin eso queda como `geom` de todos
modos en versiones recientes, pero prefiero ser explícito.

## La función

```sql
DROP FUNCTION IF EXISTS geocodificar(double precision, double precision);

CREATE FUNCTION geocodificar(p_lat double precision, p_lon double precision)
RETURNS TABLE (
  colonia text, cp text, municipio text,
  municipio_cod int, seccion text, distrito_l text
)
LANGUAGE sql
STABLE
AS $$
  SELECT
    c.nombre::text                                       AS colonia,
    c.cp::text                                           AS cp,
    m.nombre::text                                       AS municipio,
    COALESCE(m.municipio, c.municipio, s.municipio)::int AS municipio_cod,
    s.seccion::text                                      AS seccion,
    s.distrito_l::text                                   AS distrito_l
  FROM (SELECT ST_SetSRID(ST_MakePoint(p_lon, p_lat), 4326) AS g) p
  LEFT JOIN colonias   c ON ST_Contains(c.geom, p.g)
  LEFT JOIN secciones  s ON ST_Contains(s.geom, p.g)
  LEFT JOIN municipios m ON ST_Contains(m.geom, p.g)
  LIMIT 1;
$$;
```

Y se usa así:

```sql
SELECT * FROM geocodificar(20.1234, -98.7654);
```

### Los detalles que no son obvios

**`ST_MakePoint(p_lon, p_lat)` — longitud primero.** Es el error más común de
PostGIS y no da error: te devuelve resultados de un punto en otro continente. En
coordenadas geográficas el orden es X, Y, o sea longitud, latitud, al revés de
como las dice todo el mundo. Fíjate en que los parámetros de la función sí van
`(lat, lon)`, porque es como llegan de un GPS; el volteo se hace adentro, una
sola vez, donde no se olvida.

**`ST_SetSRID(..., 4326)`** le dice a PostGIS en qué sistema de referencia está
ese punto. Un punto sin SRID no se puede comparar con un polígono que sí lo
tiene.

**`LEFT JOIN` y no `JOIN`.** Un punto puede caer dentro de una sección electoral
pero fuera de cualquier colonia mapeada — pasa constantemente en zonas rurales y
en fraccionamientos nuevos. Con `JOIN` la consulta devuelve cero filas y pierdes
también el dato de la sección. Con `LEFT JOIN` devuelves lo que sí sabes y `NULL`
lo que no.

**El `COALESCE` de tres fuentes para la clave del municipio.** Las tres capas
traen la clave INEGI, y cada una tiene huecos distintos. Se toma la primera que
esté.

**`STABLE`** le dice a PostgreSQL que la función devuelve lo mismo dentro de una
misma consulta. Eso permite optimizarla cuando se llama desde un `SELECT` sobre
muchas filas. No es `IMMUTABLE` porque lee tablas.

## El truco para poder crearla antes que las tablas

```sql
SET check_function_bodies = false;
```

Por omisión, PostgreSQL valida el cuerpo de una función al crearla, así que si
`colonias` todavía no existe, el `CREATE FUNCTION` falla.

Con esa línea, el cuerpo se valida en tiempo de ejecución. Eso permite que el
script de esquema corra completo en una base vacía y los shapefiles se importen
después, en cualquier orden. Sin ella, el orden de instalación se vuelve frágil y
alguien va a tropezar con él.

## Cuánto tarda

Con el índice GIST, una consulta de este tipo sobre unos miles de polígonos está
en el orden de milisegundos. Es más rápido que la llamada HTTP que harías a un
servicio externo, y no tiene cuota.

Si lo vas a llamar sobre una tabla entera —geocodificar cien mil registros
históricos—, hazlo por lotes y con un `WHERE` que salte lo ya resuelto, para poder
interrumpirlo y retomarlo:

```sql
UPDATE reportes r
SET colonia = g.colonia, seccion = g.seccion
FROM geocodificar(r.lat, r.lon) g
WHERE r.colonia IS NULL
  AND r.id IN (SELECT id FROM reportes WHERE colonia IS NULL LIMIT 5000);
```

## Lo que gana más allá de la dirección

El dato que de verdad vale no es la colonia: es la **sección electoral**.

Es la unidad territorial mínima del país, tiene entre cien y tres mil electores,
y es la llave con la que se cruzan los resultados electorales históricos, las
estadísticas censales del INEGI por AGEB y cualquier padrón propio.

Un punto convertido en sección deja de ser una dirección y se vuelve una fila que
puedes cruzar con todo lo demás. Esa conversión es la que hace que una plataforma
territorial sirva para algo más que pintar puntos en un mapa.
