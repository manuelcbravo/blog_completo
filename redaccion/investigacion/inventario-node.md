# Inventario de `Desktop\node` · notas de campo

Segunda inspección: `C:\Users\chain\OneDrive\Desktop\node`. Aquí viven los bots
de WhatsApp con n8n y las aplicaciones de React Native. Es material distinto al
de `laragon\www` —ahí todo es Laravel— y da para una tanda entera.

Fecha: agosto de 2026.

---

## Qué hay

| Carpeta | Qué es |
| --- | --- |
| `territorio-app` | React Native + Expo SDK 54. App de campo de la plataforma Territorio |
| `agentis_client_app` / `agentis_user_app` | React Native + Expo. Dos apps del mismo producto, cliente y usuario |
| `konfido-erp-front` | React con HeroUI, Google Maps, Highcharts, GA4, reCAPTCHA |
| `reporta-bache` | n8n + PostGIS. Reportes ciudadanos de baches |
| `reporta-bache-ycloud` / `reporta-bache_dualhook` | El mismo, con dos proveedores distintos de WhatsApp |
| `pycos_bot` | n8n. Bot de pedidos de pastel conectado al POS |
| `pipe-dualhook` | n8n. Bot de captación en gira de la plataforma electoral |
| `agency-agents` | Sin `package.json`; no lo inspeccioné |

Los cuatro proyectos de n8n comparten estructura: `README.md`, `docs/`, `n8n/`
con los workflows en JSON, `scripts/` y `sql/`. Están documentados mejor que la
mayoría del código Laravel.

---

## 1. territorio-app: el documento más valioso de los dos inventarios

`docs/PLAN.md` no es un README, es un documento de decisiones con los porqués
escritos. De ahí sale la mitad de esta tanda.

### Las tres decisiones que se declaran al inicio

**Qué tan offline y para quién.** No es una decisión técnica, es de volumen:

| Rol | Registros a su alcance | Modo |
| --- | --- | --- |
| Promotor | decenas a cientos | Offline completo |
| Coordinador | cientos a miles | Offline completo con tope |
| Administrador | **235,613** | En línea con caché |

Con un tope explícito: si el pull inicial pasa de ~5,000 filas, se baja la
ventana de los últimos 12 meses y el resto se consulta en línea, avisando en
pantalla qué está descargado y qué no. La frase del documento:

> «un usuario que cree tener todo y no lo tiene es peor que uno que sabe que le
> falta».

Y la regla que no se negocia: **la captura siempre es offline, para todos los
roles**. Es el punto de la app.

**Identidad de los registros de campo.** UUID v4 generado por el dispositivo,
que viaja en la subida y manda sobre el `id` al resolver a qué registro apunta
la captura. Su razón de ser es una sola: idempotencia. Una subida cortada a la
mitad se reintenta completa y no duplica.

**Qué pasa cuando los dos lados editaron.** Aquí hay una decisión honesta y poco
común: *el servidor no compara versiones*, no hay `base_updated_at` ni respuesta
de conflicto, y la app **respeta eso en lugar de simular una detección que del
otro lado no existe**.

Lo que sí distingue son cinco desenlaces de la cola: alta local gana, `aplicada`
sale de la cola, `rechazada` sale y va a «Requieren tu atención», `fallida` se
reintenta con espera creciente, y la baja va como `DELETE` fuera del lote y
vuelve como lápida.

> «Una app que reintenta lo rechazado cada cinco minutos se queda con la cola
> atorada para siempre.»

### El motor de sincronización

Cinco pasos, con dos detalles que valen un artículo:

**Subir antes que bajar, siempre.** Para que la bajada no sobrescriba trabajo
local que todavía no salió del dispositivo.

**El `desde` es el reloj del servidor, nunca el del teléfono** —que puede estar
mal puesto— y es el del *primer* tramo de la pasada: con el del último,
cualquier fila tocada mientras se paginaba quedaría del lado ya visitado y no
bajaría nunca.

Más: a los 5 fallos la operación deja de reintentarse sola; y las colecciones
que el usuario no alcanza ni se piden, porque pedirlas produce un 403
garantizado en cada pasada.

### La captura de INE

**El PDF417 del reverso ya no existe.** Los modelos nuevos de la credencial lo
sustituyeron por un QR de alta densidad, comprimido y encriptado, pensado para
que la app *Valida INE-QR* verifique el plástico contra los servidores del INE.
Un lector de códigos devuelve bytes cifrados. Está escrito en el documento
«para que nadie vuelva a intentarlo».

Lo que sí es texto plano son la MRZ del reverso y las etiquetas del frente. Todo
sale de OCR.

**La regla de precedencia:** CURP, clave de elector, MRZ, frente. Porque la CURP
y la clave llevan fecha de nacimiento y sexo dentro de su estructura y se
validan solas, mientras que el nombre reconocido sobre un plástico rayado es una
interpretación. Y cuando dos fuentes que se validan solas discrepan, **no se
resuelve solo**.

**El problema del siglo:** la clave de elector guarda el año con dos dígitos y no
trae desempate; la CURP sí (carácter 17: dígito antes del 2000, letra a partir
del 2000). «Es exactamente el error que produce gente de 1907 en el padrón.»

**Números medidos** sobre 3,000 claves reales: el formato reconoce el 88.7%, el
sexo coincide en el 97.8%, día y mes en el 93.1%. Y el análisis del 11% que
falla: claves vacías, truncadas, con letra donde va dígito, y 29 CURP capturadas
en el campo equivocado.

**La captura no tiene obturador:** dispara sola cada 1.1 s y acumula, diciendo
qué falta —«ahora voltéala»—. Porque quien captura tiene la credencial en una
mano y el teléfono en la otra, de pie en la puerta de una casa.

**El costo:** ML Kit es módulo nativo, no corre en Expo Go, exige development
build. Y en un binario sin el módulo, la pantalla lo dice y ofrece capturar a
mano.

**Datos personales:** por defecto se extraen los campos y la imagen no se
conserva. «Es más barato decidirlo ahora que después de tener 40,000
credenciales en los teléfonos.»

→ *Da para cuatro artículos: offline por rol, el motor de sincronización, el OCR
del INE y el costo de salir de Expo Go.*

---

## 2. Los bots de n8n: tres máquinas de estados

### La decisión de arquitectura que comparten

De `pipe-dualhook/sql/01_conversaciones.sql`, en el propio comentario:

> La máquina de estados del bot la escribe y la lee únicamente n8n. Al vivir
> aquí, el servidor de n8n **no necesita abrir ninguna conexión a la base de la
> plataforma**: todo lo demás va por HTTPS contra su API.

Y tabla aparte por bot, aunque compartan instancia de n8n, «porque son dos
máquinas de estados distintas y no deben cruzarse».

La tabla es minúscula y hace todo el trabajo:

```sql
CREATE TABLE conversaciones_pipe (
  telefono    text PRIMARY KEY,
  estado      text  NOT NULL DEFAULT 'nuevo',
  datos       jsonb NOT NULL DEFAULT '{}'::jsonb,
  actualizado timestamptz NOT NULL DEFAULT now()
);
```

Con un índice sobre `actualizado` cuyo único propósito documentado es poder
purgar conversaciones abandonadas de más de 30 días.

### pipe-dualhook: el consentimiento como barrera

El aviso de datos va primero y **sin «acepto» no se guarda ni el nombre**. El
consentimiento se registra con fecha y **versión del aviso**: si el texto cambia,
a quien aceptó la versión anterior se le vuelve a preguntar. La palabra `BAJA`
borra los datos en cualquier momento.

### pipe-dualhook: diseñar para que no canse

Tres decisiones, cada una elimina preguntas:

| Decisión | Qué ahorra |
| --- | --- |
| El código de gira llega en el primer mensaje, por QR o liga `wa.me/…?text=GIRA-TULA-0612` | No se pregunta el evento ni el municipio |
| Sexo inferido del nombre de pila; solo se pregunta si es ambiguo (Guadalupe, Cruz, René) | Una pregunta menos casi siempre |
| Una sola pregunta de ubicación que acepta GPS, código postal o texto libre | Cuatro preguntas en una |

Y la gestión se ofrece **al final, en una sola pregunta**, «para que quien solo
quiere quedar registrado no tenga que inventarse un problema».

### pycos_bot: las restricciones de WhatsApp cambian el diseño

De `docs/ESTADO.md`, que es un documento de estado sinceramente escrito:

- **El título de un botón de WhatsApp no puede pasar de 20 caracteres.** Por eso
  el botón dice «Asistente virtual» y no «Tomar pedido con asistente virtual».
- **Modo silencio:** con «Esperar a un asesor» el bot deja de contestar, y vuelve
  con la palabra `bot` o al pasar dos horas.
- **El saludo duplicado:** «Gracias por comunicarte con Pycos» sale de la app de
  WhatsApp Business, no del código, y hay que quitarlo de ahí o salen dos
  saludos encimados.
- Botones interactivos en todos los pasos de tres opciones o menos.

Y el detalle más honesto del documento: entre lo que falta corregir antes de
usarlo con clientes reales, «el más importante: la imagen de decoración trae
precios inventados».

Además usa un canal propio del POS, `/api/bot/*`, y no el canal web, para que el
pedido caiga en la sucursal física.

→ *Da para cuatro artículos: dónde vive el estado, el consentimiento, diseñar
para que no canse, y las restricciones de la plataforma.*

---

## 3. PostGIS de verdad, en `reporta-bache`

Dos funciones que resuelven los dos problemas de cualquier plataforma
territorial.

**Geocodificación inversa** con `ST_Contains`: dado un punto devuelve colonia,
CP, municipio con su código INEGI, sección electoral y distrito local, cruzando
tres capas en una consulta.

**Deduplicación por cercanía** con `ST_DWithin`, radio de 10 metros por omisión.
Y el detalle que importa: la columna es `geography(Point,4326)`, no `geometry`,
así que **la distancia sale en metros** sin proyecciones ni conversiones.

Índices GIST sobre ambas, «clave para ST_DWithin / ST_Distance».

La importación de los shapefiles está en un script de PowerShell con la línea
que resume medio día de trabajo:

```powershell
shp2pgsql -s 32614:4326 -W LATIN1 -I -D -g geom colonias.shp public.colonias | psql
```

`32614:4326` reproyecta de UTM zona 14N a WGS84, y `-W LATIN1` es la
codificación en la que vienen los shapefiles oficiales mexicanos: sin eso, cada
«Ñ» y cada acento entran rotos.

Y un generador de folios consecutivos por estado y año con `INSERT … ON CONFLICT
DO UPDATE`, que es la forma correcta de un contador sin condiciones de carrera.

→ *Da para dos artículos: geocodificación inversa y deduplicación por cercanía.*

---

## 4. Lo que no alcancé a inspeccionar

`agentis_client_app` y `agentis_user_app` tienen `src/storage/sqlite` y
`src/services/network`, o sea el mismo patrón offline-first, pero **no tienen
documentación**. Para escribir sobre ellas habría que leer el código a fondo, y
preferí sacar la tanda del proyecto que sí explica sus decisiones.

`konfido-erp-front` es el único frontend de React sin Inertia del inventario, con
HeroUI, Google Maps, GA4 y reCAPTCHA. Es material para un artículo sobre cuándo
un frontend separado sí se justifica, pero necesita una lectura que no hice.

`agency-agents` no tiene `package.json` y no lo abrí.
