---
titulo: Dónde vive el estado de un bot de WhatsApp
slug: donde-vive-el-estado-de-un-bot-de-whatsapp
tipo: post
estado: borrador
categoria: Integraciones
etiquetas: [n8n, whatsapp, postgresql, arquitectura, api]
resumen: Cuatro columnas y una tabla en la base de n8n, no en la de tu plataforma. La decisión parece administrativa y define quién puede tumbar tu producción a las tres de la mañana.
meta_descripcion: Cómo guardar el estado conversacional de un bot de WhatsApp construido con n8n, por qué separarlo de la base de la aplicación y cómo modelar la máquina de estados.
hace_dias: 22
---

Tengo tres bots de WhatsApp corriendo sobre n8n: uno levanta reportes ciudadanos
de baches, otro toma pedidos de una pastelería y otro registra gente en giras de
campaña.

Los tres comparten una decisión que tomé mal la primera vez.

## El estado tiene que vivir en algún lado

Una conversación de WhatsApp es una máquina de estados. La persona escribe
«hola», el bot pregunta el nombre, ella contesta, el bot pregunta la edad. Entre
un mensaje y el siguiente pueden pasar tres segundos o dos días.

Cada mensaje que llega es una petición HTTP independiente. Para saber qué
contestar hay que recordar en qué paso iba ese teléfono y qué había respondido
hasta ahora.

La primera vez lo guardé en la base de la plataforma, porque ya estaba ahí. Fue
un error, y no por rendimiento.

## Por qué no en la base de la aplicación

Si n8n escribe en la base de tu plataforma, entonces **el servidor de n8n
necesita credenciales de esa base**. Y con eso te llevas tres cosas que no
querías:

**Una ruta de acceso más a tus datos.** n8n es una aplicación web con interfaz
de administración, plugins y una superficie de ataque propia. Si alguien entra a
n8n, entró a tu base de producción.

**Un acoplamiento de esquema.** Cambias una tabla en la plataforma y puedes
romper un workflow que vive en otro servidor, en un JSON, sin pruebas y sin que
nadie se entere hasta que un ciudadano recibe un mensaje raro.

**Un problema de red.** La base deja de poder estar cerrada al mundo: tiene que
aceptar conexiones desde donde corra n8n.

La versión que quedó, escrita como comentario en el propio SQL:

> La máquina de estados del bot la escribe y la lee únicamente n8n. Al vivir
> aquí, el servidor de n8n **no necesita abrir ninguna conexión a la base de la
> plataforma**: todo lo demás va por HTTPS contra su API.

n8n tiene su propia base —la que usa para sus workflows y ejecuciones—. Ahí va la
tabla de conversaciones. Y todo lo que sea dato de negocio viaja por la API,
autenticado con un token, validado por Form Requests, con las mismas reglas que
cualquier otro cliente.

**La plataforma no tiene una puerta trasera para el bot. El bot es un cliente
más.**

## La tabla completa

```sql
CREATE TABLE IF NOT EXISTS conversaciones_pipe (
  telefono    text PRIMARY KEY,
  -- nuevo | esperando_nombre | esperando_edad | esperando_sexo
  -- | esperando_ubicacion | esperando_gestion | esperando_tipo
  -- | esperando_descripcion | cerrado
  estado      text  NOT NULL DEFAULT 'nuevo',
  -- acumula: gira_codigo, nombre, ap_paterno, ap_materno, edad, sexo,
  -- ubicacion{}, registro_completo, gestion{}, folios[]
  datos       jsonb NOT NULL DEFAULT '{}'::jsonb,
  actualizado timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_conversaciones_pipe_actualizado
  ON conversaciones_pipe (actualizado);
```

Eso es todo. Cuatro columnas.

**`telefono` como llave primaria.** Una persona, una conversación. No hay
`id` autoincremental porque no hace falta: el identificador natural existe y es
único. Y hace que el `UPSERT` de cada mensaje sea trivial.

**`estado` es texto con los valores en un comentario, no un enum de PostgreSQL.**
Deliberado: los estados de un bot cambian cada semana mientras se afina el
diálogo, y `ALTER TYPE ... ADD VALUE` en medio de una demo con el cliente
enfrente no es donde quieres estar. El comentario documenta y la aplicación
valida.

**`datos` es `jsonb` y acumula.** El bot va guardando lo que recoge sin que cada
campo nuevo sea una migración. Cuando el cliente pide «pregunta también la
colonia», es una clave más en el JSON y un nodo más en n8n. Cero cambios de
esquema.

Es lo contrario de lo que haría en la plataforma, donde cada campo va en su
columna con su tipo. Aquí la forma cambia demasiado seguido y el dato es
temporal: en cuanto la conversación termina, lo que importa ya viajó por la API a
una tabla bien modelada.

**El índice sobre `actualizado` tiene un solo propósito**, y está documentado:

```sql
DELETE FROM conversaciones_pipe WHERE actualizado < now() - interval '30 days';
```

Higiene. Mucha gente abandona una conversación a la mitad; sin purga, la tabla
acumula estados muertos para siempre.

## Una tabla por bot, aunque compartan n8n

Los tres bots corren en la misma instancia de n8n y cada uno tiene su tabla.

Podría haber puesto una sola con una columna `bot`. No lo hice porque **son
máquinas de estados distintas y no deben cruzarse**: los estados de la pastelería
no significan nada para el de baches, y una consulta mal filtrada leería el
estado equivocado del mismo teléfono.

Además, el mismo número puede estar conversando con dos bots a la vez. Con tablas
separadas eso funciona solo; con una tabla y `telefono` como llave, es un
conflicto.

## Lo que ganas con esta separación

**Puedes tirar y rehacer n8n sin tocar la plataforma.** Cambiar de proveedor de
WhatsApp, migrar el servidor, reimportar workflows: nada de eso roza los datos de
negocio.

**Puedes probar el bot sin WhatsApp.** Como todo entra por HTTP y sale por HTTP,
se dispara el workflow a mano con un mensaje simulado y se ve la conversación
avanzar en la tabla. Los tres proyectos tienen documentado cómo probarlos sin
tener el número conectado.

**El bot no puede corromper tus datos.** Lo peor que puede hacer es mandar una
petición mal formada a tu API, y tu API sabe qué hacer con eso.

## El precio

Ser justo: hay una llamada HTTP donde había una consulta SQL. Es más lento y
puede fallar por red.

En un bot de WhatsApp da exactamente igual. La persona del otro lado tarda quince
segundos en teclear la respuesta; ochenta milisegundos de latencia no existen. Y
si la API no contesta, quieres que el bot lo diga y reintente, no que escriba a
medias en tu base.

Es el tipo de costo que hay que medir contra el caso de uso y no contra el ideal
de eficiencia. Aquí sobra presupuesto de tiempo, y falta presupuesto de riesgo.
