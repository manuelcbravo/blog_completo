---
titulo: 'Offline-first en React Native: la pregunta no es cómo, es para quién'
slug: offline-first-en-react-native-por-rol
tipo: post
estado: borrador
categoria: Móvil
etiquetas: [react-native, expo, sqlite, offline, arquitectura]
resumen: 235,613 registros no caben en un teléfono. Pero el promotor que los captura sólo necesita los suyos. Cómo el volumen por rol define la arquitectura, y por qué la captura sí es offline para todos.
meta_descripcion: Arquitectura offline-first en React Native con SQLite y Expo, decidida por volumen y por rol en lugar de aplicar el mismo modo a toda la aplicación.
hace_dias: 8
---

La conversación sobre offline-first casi siempre empieza mal. «¿La app va a
funcionar sin internet?» es una pregunta binaria para un problema que no lo es.

En la app de campo de una plataforma territorial la respuesta correcta resultó
ser: **depende de quién la abra**.

## El número que decide

La plataforma tiene 235,613 personas registradas.

Bajar eso a un teléfono no es imposible —SQLite lo aguanta— pero es una descarga
inicial de varios minutos, un consumo de datos que nadie va a pagar, y una
sincronización que hay que resolver para un conjunto que el usuario jamás va a
mirar entero.

Y no hace falta. Un promotor de campo tiene **decenas o cientos** de personas a
su alcance. Un coordinador, cientos o miles. Sólo el administrador ve las
235,613, y él trabaja sentado frente a una computadora con wifi.

De ahí sale la tabla que gobierna toda la arquitectura:

| Rol | Registros a su alcance | Modo |
| --- | --- | --- |
| Promotor | decenas a cientos | **Offline completo.** Se baja todo lo suyo |
| Coordinador | cientos a miles | **Offline completo** con tope |
| Administrador | 235,613 | **En línea con caché.** Búsqueda contra el servidor |

El tope del coordinador: si la descarga inicial pasa de unas 5,000 filas, se baja
la ventana de los últimos doce meses y el resto se consulta en línea.

Y una regla que va en la interfaz, no en el código: **se avisa en pantalla qué
está descargado y qué no**. Un usuario que cree tener todo y no lo tiene es peor
que uno que sabe que le falta, porque el primero va a concluir que el registro
que busca no existe.

## Lo que sí es igual para todos

**La captura siempre es offline.** Para los tres roles, sin excepción.

Ese es el punto entero de la aplicación: dar de alta a alguien de pie en la
puerta de su casa, en una colonia sin señal, y que suba después. Si eso falla, la
app no sirve, aunque la consulta funcione de maravilla.

Es una distinción útil en cualquier app de campo: **leer puede degradarse, escribir
no**. Un listado que dice «no pude actualizar, esto es de hace dos horas» es
aceptable. Un formulario que pierde lo que alguien acaba de teclear, no.

## La forma de la base local

SQLite con `expo-sqlite`, una tabla por colección y una tabla de control:

```
territorio.db
├─ promovidos       espejo de lo visible + lo creado en campo
├─ promotores       espejo de promotores
├─ solicitudes      con seguimientos y verificaciones anidados como JSON
├─ verificaciones   capturadas en el dispositivo
├─ catalogos        los 19 catálogos y la geografía, en una sola tabla
├─ sync_outbox      la cola de salida
└─ kv               relojes, cursores y firmas
```

Dos decisiones ahí que ahorran trabajo.

**Los catálogos van todos en una sola tabla**, con el mismo nombre que usa la
API. Diecinueve catálogos son diecinueve tablas de tres columnas que no se
consultan nunca por separado; una tabla con una columna `catalogo` y un JSON
hace lo mismo y la sincronización es una sola.

**Lo anidado que sólo se lee se guarda como JSON.** Los seguimientos de una
solicitud llegan dentro de ella y no se editan desde el móvil. Normalizarlos en
su propia tabla sería trabajo para nada: se guardan como vienen.

## Las columnas de control

Cada tabla que se sincroniza lleva las mismas seis columnas. Esto es lo que hace
que el motor de sincronización sea uno solo y no uno por colección:

| Columna | Para qué |
| --- | --- |
| `uuid` | La llave que generó el teléfono. Es la primaria local y la que viaja |
| `id` | El identificador del servidor. `NULL` hasta que sube por primera vez |
| `sync_estado` | `sincronizado` · `pendiente` · `enviando` · `rechazado` |
| `actualizado_en` | Cuándo lo tocó el servidor |
| `actualizado_local` | Última edición en el dispositivo |
| `rechazo_json` | Los errores campo por campo cuando el servidor lo rechazó |

**El `uuid` como llave primaria local es la decisión clave.** El dispositivo crea
un registro sin conexión; no puede pedirle un `id` al servidor porque no hay
servidor. Necesita un identificador propio desde el instante cero, y necesita que
ese identificador siga siendo válido después de subir.

Lo que compra es idempotencia: una subida que se corta a la mitad se reintenta
completa y **no duplica**, porque el servidor reconoce el `uuid` que ya insertó.
Sin eso, cada colonia con mala señal genera registros repetidos.

El `id` del servidor se guarda cuando llega, pero el `uuid` es el que manda al
resolver a qué registro apunta una captura.

## Dos columnas de fecha, no una

`actualizado_en` y `actualizado_local` parecen redundantes hasta que hay que
decidir qué mostrar.

La primera dice cuándo el servidor tocó la fila. La segunda, cuándo la tocó el
teléfono. Con las dos puedes contestar «esto que ves lo editaste tú hace diez
minutos y todavía no sube», que es información que el usuario necesita y que con
una sola columna es imposible dar.

## Cuándo se dispara

Tres momentos: al abrir la app, al recuperar conexión —`NetInfo` avisa— y con un
tirón manual hacia abajo.

El tirón manual no es decorativo. En campo, la gente sabe cuándo tiene señal
—acaba de subir a la avenida— y quiere sincronizar en ese momento, no cuando a la
app le parezca.

## Lo que no intenté resolver

Sincronización bidireccional con resolución automática de conflictos. El servidor
de esta plataforma no compara versiones: la última escritura queda, y no hay
respuesta de conflicto.

Podría haber simulado una detección del lado del móvil, guardando la versión base
y comparando. Decidí no hacerlo: **una detección de conflictos que el otro lado no
respalda es teatro**, y da una confianza que no corresponde a lo que realmente
pasa con los datos.

Es más honesto documentar que gana la última escritura, diseñar el reparto de
trabajo para que dos personas no editen lo mismo, y gastar ese esfuerzo en que la
cola de salida sea impecable.

## Si estás por decidirlo

La pregunta con la que empezaría, antes que cualquier otra sobre bibliotecas:

**¿Cuántos registros tiene a su alcance el usuario más común de la app?** No el
total de la base. El del usuario que va a abrirla veinte veces al día.

Si son cientos, offline completo y no lo pienses más. Si son cientos de miles,
caché de lo que abre y búsqueda en línea. Y si la respuesta cambia mucho entre un
rol y otro —que es lo normal—, entonces la arquitectura tiene que cambiar con
ella, en lugar de aplicarle a todos el modo del caso más difícil.
