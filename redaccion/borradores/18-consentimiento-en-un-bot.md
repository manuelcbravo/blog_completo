---
titulo: El consentimiento como barrera de verdad, no como casilla
slug: consentimiento-en-un-bot-de-whatsapp
tipo: post
estado: borrador
categoria: Seguridad
etiquetas: [whatsapp, n8n, datos-personales, lfpdppp, privacidad]
resumen: Sin «acepto» no se guarda ni el nombre. El consentimiento lleva versión, y si el aviso cambia se vuelve a preguntar. La palabra BAJA borra todo. Cuesta tres nodos y evita una multa.
meta_descripcion: Cómo implementar el consentimiento informado en un bot de WhatsApp que captura datos personales, con versión del aviso, baja a petición y registro de la aceptación.
hace_dias: 29
---

Un bot que registra ciudadanos en una gira de campaña captura nombre, edad,
sexo, ubicación y, si la persona quiere, la descripción de un problema que le
afecta.

Todo eso es dato personal bajo la Ley Federal de Protección de Datos Personales
en Posesión de los Particulares. Parte —la afiliación política que se infiere del
contexto— es **dato sensible**, con un régimen más estricto.

Lo que sigue es cómo quedó implementado, que resultó más simple de lo que
temía.

## El aviso va primero y es una barrera real

El primer mensaje del bot no pregunta el nombre. Presenta el aviso de datos y
pide permiso.

```
aviso de datos ──> nombre ──> edad ──> [sexo] ──> ubicación ──> ¿gestión?
      │
   no acepta
      │
 se cierra sin guardar nada
```

**Sin «acepto» no se guarda ni el nombre.** No es una casilla que se marca sola
ni una línea de letra chica al final: la conversación no avanza.

Es la diferencia entre consentimiento y trámite. Un aviso que aparece después de
haber capturado los datos no es consentimiento, es notificación — y la ley pide
lo primero.

La objeción de negocio es obvia: *pierdes registros*. Sí, pierdes a quien no
quiere estar. Ese es exactamente el punto, y además esos registros eran los que
te iban a traer el problema.

## El consentimiento lleva versión

Este es el detalle que casi nadie implementa y que cambia todo.

El aviso se guarda con **fecha y versión**:

```
PIPE_AVISO_VERSION=2
```

Si el texto del aviso cambia —porque se agregó una finalidad, porque ahora los
datos se comparten con otra área, porque cambió el responsable—, a quien aceptó
la versión anterior **se le vuelve a preguntar**.

La razón es de fondo: la persona consintió a un texto concreto, no a la idea
abstracta de que uses sus datos. Si el texto cambia, lo que aceptó ya no
describe lo que estás haciendo, y el consentimiento no cubre lo nuevo.

Sin versión, la única manera de saber a qué aceptó alguien en marzo es adivinar,
o revisar el historial de git del texto y cruzarlo con las fechas. Con un número
en la columna, es una consulta.

## BAJA borra los datos

En cualquier momento de la conversación —o después, escribiendo al mismo
número— la palabra **BAJA** borra los datos de esa persona.

Los derechos ARCO de la ley mexicana —acceso, rectificación, cancelación,
oposición— normalmente se ejercen escribiendo un correo a un responsable y
esperando hasta veinte días hábiles. Aquí la cancelación es una palabra en el
mismo canal por el que la persona entregó los datos.

Que sea fácil no es sólo cumplimiento: **es la prueba de que el consentimiento
era real**. Un permiso que no puedes retirar sin trámite no era un permiso.

Vale la pena instrumentar bien la baja del lado de la plataforma: borrar de
verdad la fila, no marcar un `activo = false` que deja los datos ahí. Si alguien
pide la cancelación, la cancelación es un `DELETE`.

## Dónde queda registrado

En la conversación, mientras dura, y en la plataforma cuando el registro sube:

```
datos.consentimiento = { aceptado: true, version: 2, fecha: '2026-08-19T...' }
```

En el bot de la pastelería, que pide bastante menos —nombre y teléfono para
avisar cuándo está el pedido—, el consentimiento se anota en las observaciones
del pedido. Es menos formal y proporcional a lo que se captura, que es como debe
ser: **el peso del consentimiento sigue al riesgo del dato**.

Ahí el bot también se presenta como asistente virtual y avisa que se puede
escribir `cancelar` en cualquier momento. Decir que del otro lado hay una máquina
no es un requisito legal en México todavía, pero es lo mínimo decente y en varias
jurisdicciones ya es obligatorio.

## Lo que costó

Tres nodos en n8n: mostrar el aviso, esperar la respuesta, guardar la decisión.
Una clave más en el JSON de la conversación. Una variable de entorno con el
número de versión.

Menos de una hora de trabajo.

Compárelo con lo que evita: una plataforma de captación ciudadana que guarda
nombre, ubicación y afiliación política inferida, sin consentimiento registrado,
en un contexto electoral. Ese es el escenario donde llega una queja al INAI y no
hay defensa posible.

## Las tres reglas que me llevo

**El aviso antes del primer dato, y que sea barrera.** Si la conversación avanza
sin aceptar, no había consentimiento.

**Versión el texto y guarda a cuál aceptó cada quien.** Es una columna, y es la
única forma de responder «¿a qué consintió esta persona?» dentro de un año.

**Que la baja sea tan fácil como el alta.** Si entrar cuesta un mensaje, salir
debe costar un mensaje.

Ninguna de las tres es cara. Las tres se vuelven imposibles de agregar cuando ya
tienes cuarenta mil registros capturados sin ellas — porque entonces no hay
manera de conseguir hacia atrás un consentimiento que nunca se pidió.
