---
titulo: 'Deduplicar por cercanía: ST_DWithin y por qué geography y no geometry'
slug: deduplicar-por-cercania-con-postgis
tipo: tutorial
estado: borrador
categoria: Geoespacial
etiquetas: [postgis, postgresql, sql, mapas, rendimiento]
resumen: Tres vecinos reportan el mismo bache desde tres esquinas. Son un bache con tres reportes, no tres baches. Cómo se resuelve con diez metros de radio y por qué el tipo de la columna decide si escribes metros o grados.
meta_descripcion: Deduplicar registros geográficos en PostGIS con ST_DWithin, la diferencia entre geography y geometry, e índices GIST para que la consulta sea rápida.
hace_dias: 50
---

Tres vecinos de la misma cuadra reportan el mismo bache. Cada uno manda su
ubicación desde donde está parado, así que llegan tres puntos separados por
cuatro, siete y once metros.

Eso es **un bache con tres reportes**, no tres baches. Si el sistema no lo
distingue, el ayuntamiento manda tres cuadrillas al mismo hoyo y los reportes
ciudadanos dejan de servir para priorizar.

La deduplicación por cercanía se resuelve con una función de PostGIS y una
decisión de tipo de columna que lo cambia todo.

## La decisión: `geography`, no `geometry`

PostGIS tiene dos tipos espaciales y elegir mal se paga en cada consulta.

**`geometry`** trabaja sobre un plano cartesiano. Es rápido y es lo correcto
cuando tus datos están proyectados —en UTM, por ejemplo, donde las unidades son
metros sobre una proyección plana—.

**`geography`** trabaja sobre el elipsoide terrestre. Es más lento y **sus
distancias salen en metros**, calculadas sobre la superficie de la Tierra.

Aquí está el problema real: si guardas puntos en `geometry` con SRID 4326 —o sea
latitud y longitud— y preguntas por una distancia, la respuesta viene **en
grados**. Y un grado no mide lo mismo en Hidalgo que en Alaska, ni mide lo mismo
en latitud que en longitud. Terminas escribiendo constantes mágicas como
`0.00009` y esperando que a nadie se le ocurra desplegar el sistema en otra
latitud.

Por eso la columna es:

```sql
geom geography(Point,4326)
```

Y por eso el radio se escribe en metros, como se piensa:

```sql
ST_DWithin(a, b, 10)   -- diez metros. Literalmente.
```

Para un país entero, la diferencia de rendimiento entre los dos tipos es
irrelevante frente a la de tener que razonar en grados.

## La función

```sql
CREATE OR REPLACE FUNCTION bache_cercano(
  p_lat     double precision,
  p_lon     double precision,
  p_radio_m double precision DEFAULT 10
)
RETURNS TABLE (id uuid, folio text, num_reportes int, distancia_m double precision)
LANGUAGE sql
STABLE
AS $$
  SELECT
    b.id,
    b.folio,
    b.num_reportes,
    ST_Distance(b.geom, ST_SetSRID(ST_MakePoint(p_lon, p_lat), 4326)::geography) AS distancia_m
  FROM baches b
  WHERE ST_DWithin(b.geom, ST_SetSRID(ST_MakePoint(p_lon, p_lat), 4326)::geography, p_radio_m)
  ORDER BY distancia_m
  LIMIT 1;
$$;
```

Devuelve el registro más cercano dentro del radio, o nada. Con eso, el flujo de
ingesta es:

```
llega un reporte
  └─ ¿hay un bache dentro de 10 m?
       ├─ sí  → num_reportes + 1, se guarda el reporte apuntando a ese bache
       └─ no  → se crea un bache nuevo con su folio
```

Y el `created` que devuelve ese paso es lo que después decide si se manda la
notificación de «bache nuevo» o la de «un reporte más refuerza este bache».

## `ST_DWithin` y no `ST_Distance < 10`

Parecen lo mismo y no lo son.

```sql
-- mal
WHERE ST_Distance(b.geom, punto) < 10

-- bien
WHERE ST_DWithin(b.geom, punto, 10)
```

`ST_Distance` calcula la distancia real de **cada fila de la tabla** contra tu
punto, y después filtra. Es un recorrido completo, siempre.

`ST_DWithin` usa el índice espacial: primero descarta con las cajas envolventes
todo lo que ni de lejos está cerca, y sólo calcula la distancia exacta de los
pocos candidatos que sobreviven. En una tabla de cien mil puntos es la diferencia
entre milisegundos y segundos.

Fíjate en que `ST_Distance` sí aparece en el `SELECT` —ahí lo quieres, para saber
a cuántos metros quedó—, pero nunca en el `WHERE`.

## El índice no es opcional

```sql
CREATE INDEX IF NOT EXISTS idx_baches_geom ON baches USING GIST (geom);
```

GIST, no BTREE. Un índice BTREE ordena valores en una línea; los polígonos y los
puntos en un plano no tienen ese orden. GIST indexa por cajas envolventes
anidadas, que es lo que `ST_DWithin` sabe aprovechar.

Sin él, `ST_DWithin` funciona pero se comporta igual de mal que `ST_Distance`.

## Elegir el radio

Diez metros salió de mirar el problema, no de una fórmula.

Es más que el error típico del GPS de un teléfono en calle abierta —entre tres y
ocho metros— y menos que la distancia entre dos baches que de verdad son
distintos. Con veinte metros empiezas a fusionar hoyos separados que están en la
misma cuadra; con cinco, el mismo bache reportado desde las dos aceras se
duplica.

Es un parámetro con valor por omisión, no una constante incrustada, precisamente
porque el número correcto depende del terreno. En una carretera podría ser
cincuenta; en un mercado, tres.

## Lo mismo sirve para otras cosas

El patrón —buscar el vecino más cercano dentro de un radio— resuelve varios
problemas que no parecen el mismo:

- **Deduplicar reportes ciudadanos**, que es este caso.
- **Validar que una captura de campo se hizo donde dice**, comparando el punto
  contra el domicilio registrado.
- **Asignar el taxi más cercano** a un servicio.
- **Detectar capturas sospechosas**: cincuenta registros en el mismo punto con
  cero metros de diferencia es alguien capturando desde su casa en vez de en
  campo.

Ese último uso lo descubrí por accidente revisando datos, y resultó ser la
herramienta de control de calidad más útil de toda la plataforma.

## Un apunte sobre folios

El bache nuevo necesita un folio consecutivo, y ahí hay una trampa clásica de
concurrencia. La forma correcta, sin bloquear ni arriesgar duplicados:

```sql
INSERT INTO folio_contador (estado, anio, consecutivo)
VALUES (p_estado, extract(year from now()), 1)
ON CONFLICT (estado, anio)
DO UPDATE SET consecutivo = folio_contador.consecutivo + 1
RETURNING consecutivo;
```

Un `INSERT ... ON CONFLICT DO UPDATE ... RETURNING` es atómico: dos peticiones
simultáneas obtienen números distintos sin que tengas que tomar un bloqueo ni
leer-y-después-escribir. Es la manera correcta de un contador en PostgreSQL, y
mucho mejor que un `SELECT MAX(...) + 1` que en concurrencia entrega el mismo
folio dos veces.
