---
titulo: Un motor de sincronización que no pierde capturas
slug: motor-de-sincronizacion-movil
tipo: post
estado: borrador
categoria: Móvil
etiquetas: [react-native, sincronizacion, api, offline, sqlite]
resumen: Subir antes que bajar. El reloj del servidor, no el del teléfono. Y no reintentar lo que ya fue rechazado. Tres reglas que parecen detalles y son la diferencia entre una cola sana y una atorada para siempre.
meta_descripcion: Cómo construir un motor de sincronización offline para una app móvil, el orden de las operaciones, el manejo de la cola de salida y los cinco desenlaces de una subida.
hace_dias: 15
---

La parte difícil de una app offline no es guardar en SQLite. Es lo que pasa
cuando vuelve la señal.

Este es el motor que quedó después de varias reescrituras, con las tres reglas
que costaron descubrir.

## El orden

```
1. auth/yo                     revalida permisos y renueva el token
2. sincronizacion/lote         la cola de altas y ediciones, máx. 100 por tanda
   DELETE <recurso>/{id}       las bajas, fuera del lote
3. sincronizacion/arranque     cuánto hay que bajar y la firma de los catálogos
4. sincronizacion/catalogos    sólo si la firma cambió
   geografia?municipio=N       de los municipios donde ya hay gente
5. sincronizacion/<coleccion>  por tramos (?cursor=) y por diferencias (?desde=)
```

### Regla 1: subir antes que bajar, siempre

Es la primera y la más importante.

Si bajas primero, la respuesta del servidor sobrescribe filas que en el teléfono
tienen ediciones que **todavía no salieron**. El usuario ve cómo su trabajo
desaparece sin ningún error de por medio, que es la peor manera de perder datos.

Subiendo primero, para cuando llega la bajada el servidor ya tiene lo local y lo
que devuelve es la verdad combinada.

### Regla 2: el `desde` es el reloj del servidor

La sincronización por diferencias pregunta «dame lo que cambió desde tal
momento». Ese momento **nunca puede ser la hora del teléfono**: un dispositivo
con la fecha mal puesta —y en campo los hay— se pierde cambios para siempre o
se los baja todos en cada pasada.

Se usa el reloj que el servidor devolvió en la pasada anterior.

Y con un matiz que costó un rato: es el reloj del **primer** tramo de esa pasada,
no del último. Si guardas el del último, cualquier fila que alguien tocó mientras
tú estabas paginando queda del lado ya visitado y **no baja nunca**. Es una
pérdida silenciosa y sin patrón, de las que sólo aparecen cuando un usuario jura
que capturó algo que no está.

### Regla 3: lo rechazado no se reintenta

Aquí es donde se define si la cola vive sana o se pudre.

Una subida tiene cinco desenlaces posibles, y cada uno se trata distinto:

| Desenlace | Qué se hace |
| --- | --- |
| **Alta hecha en el móvil** | Gana el móvil. Es dato nuevo, no hay con qué chocar |
| **`aplicada`** | Sale de la cola. La fila completa, con el folio que asignó el servidor, llega en la bajada de esa misma pasada |
| **`rechazada`** | Sale de la cola, se marca `rechazado` y aparece en «Requieren tu atención» con los errores campo por campo |
| **`fallida`** | Se queda en la cola. Reintento con espera creciente |
| **Baja** | `DELETE` del recurso, fuera del lote. Vuelve como lápida en el siguiente delta |

La distinción que importa es **`rechazada` contra `fallida`**, y es de las que se
confunden con facilidad porque las dos «no funcionaron».

`fallida` es transitoria: se cayó la base, se fue la conexión, el servidor
devolvió un 500. Eso se reintenta, porque el mismo envío puede funcionar en cinco
minutos.

`rechazada` es definitiva: no pasó la validación, o el registro dejó de estar al
alcance de quien captura. **No la va a pasar por insistir.** Una app que
reintenta lo rechazado cada cinco minutos se queda con la cola atorada para
siempre, quemando batería y datos para recibir el mismo 422 hasta el fin de los
tiempos. Y peor: como la cola nunca se vacía, lo que sí se podía subir queda
atrás de la basura.

Lo rechazado sale de la cola y pasa a una bandeja donde una persona ve **qué
campo estuvo mal** —para eso está la columna `rechazo_json`— y lo corrige.

## El tope de reintentos

A los cinco fallos, la operación deja de reintentarse sola y aparece en la
bandeja.

Reintentar para siempre con espera creciente suena prudente y no lo es: si algo
falló cinco veces seguidas, es improbable que la sexta sea distinta, y el usuario
merece enterarse en vez de que la app siga intentando en silencio hasta que se
descubra por accidente tres semanas después.

## Lo que no se pide, no se pide

Las colecciones que el usuario no alcanza por permisos **ni se solicitan**.

Un promotor no tiene el permiso de gestionar promotores. Pedirle el padrón de
promotores en cada pasada produce un 403 garantizado, más ruido en los logs del
servidor y más tiempo de sincronización, a cambio de nada.

El paso 1 —`auth/yo`— existe justo para eso: revalida los permisos al inicio de
cada pasada, y el motor arma la lista de colecciones a pedir con lo que ese
usuario realmente alcanza.

## El agujero que sigue abierto

Uno que no se resuelve con lápidas, y prefiero tenerlo escrito.

Si a una persona la reasignan de un promotor a otro, **sale del alcance del
primero sin lápida**: para el servidor no se borró, cambió de dueño. El delta no
trae nada, y el teléfono del primer promotor conserva un registro que ya no le
corresponde.

La solución que quedó es tosca y funciona: **cada respuesta trae un `total`**, y
la app lo compara contra lo que tiene guardado. Si no cuadra, rehace la carga
completa de esa colección.

Es más caro que un delta, pasa poco, y cierra un hueco que de otro modo deja
datos fantasma en el dispositivo.

## La firma de los catálogos

Detalle pequeño con mucho retorno: el paso de arranque devuelve una **firma** de
los catálogos —un hash de su contenido—. Si no cambió, el paso 4 se salta entero.

Los diecinueve catálogos cambian dos o tres veces al año. Bajarlos en cada
sincronización es la clase de desperdicio que no se nota en wifi y sí en una
conexión de campo.

## Lo que aprendí

Un motor de sincronización se juzga por lo que hace cuando algo sale mal, no por
lo que hace en el camino feliz. Y en cada regla de este motor hay un error que ya
cometí.

Si tuviera que quedarme con una: **subir antes que bajar**. Las otras dos
producen datos incompletos, que es malo. Esa produce datos perdidos, que es
imperdonable.
