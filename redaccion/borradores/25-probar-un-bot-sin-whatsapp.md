---
titulo: Cómo probar un bot de WhatsApp sin WhatsApp
slug: probar-un-bot-de-whatsapp-sin-whatsapp
tipo: tutorial
estado: borrador
categoria: Integraciones
etiquetas: [n8n, whatsapp, pruebas, api, bots]
resumen: Probar una máquina de estados mandándote mensajes desde tu propio celular es lento, ensucia el número y no se puede repetir. Todo el flujo se ejercita con curl y una tabla.
meta_descripcion: Probar un bot de WhatsApp construido con n8n sin conectar el número real, simulando mensajes entrantes y verificando el estado de la conversación.
hace_dias: 78
---

La primera versión de un bot se prueba mandándote mensajes a ti mismo. Funciona
un rato, hasta que el flujo tiene doce pasos y para llegar al que estás
depurando tienes que teclear once respuestas.

Peor: no puedes repetir la misma conversación dos veces sin borrar el estado a
mano, ensucias el número real con pedidos de prueba, y no puedes probar nada
antes de tener la cuenta de WhatsApp aprobada.

Se resuelve entendiendo una cosa: **el bot no habla WhatsApp, habla HTTP**.

## Qué es realmente un mensaje entrante

Cuando alguien escribe, el proveedor —Meta directamente, o un intermediario—
hace un `POST` a un webhook con un JSON. Ese webhook es un nodo de n8n con una
URL.

Todo lo que hay después de esa URL es lógica que no sabe de dónde vino el
mensaje. Si mandas tú el mismo `POST` con la misma forma, el bot no distingue.

```bash
curl -X POST https://n8n.midominio.com/webhook/mi-bot \
  -H "Content-Type: application/json" \
  -d '{
    "telefono": "5217711318736",
    "texto": "GIRA-TULA-0612"
  }'
```

Ese es el primer mensaje de la conversación. Y este el segundo:

```bash
curl -X POST https://n8n.midominio.com/webhook/mi-bot \
  -H "Content-Type: application/json" \
  -d '{"telefono": "5217711318736", "texto": "Manuel Cerda"}'
```

La forma exacta del JSON depende de tu proveedor. Lo importante es que sea la
misma que llega de verdad, así que conviene sacarla de una ejecución real: n8n
guarda el cuerpo de cada ejecución y de ahí se copia el JSON tal cual.

## Ver el estado, que es lo que de verdad quieres

Mandar el mensaje es la mitad. La otra es comprobar que la máquina de estados
avanzó:

```sql
SELECT estado, datos, actualizado
FROM conversaciones_pipe
WHERE telefono = '5217711318736';
```

Esa consulta es el equivalente a mirar dentro del bot. Después del primer mensaje
debería decir `esperando_nombre`; después del segundo, `esperando_edad` y con el
nombre ya dentro del JSON.

Cuando algo no funciona, esa tabla te dice si el problema fue no entender el
mensaje, no guardar el dato o no avanzar de estado. Tres causas distintas que
desde WhatsApp se ven idénticas: el bot «no contestó bien».

## Empezar donde te interesa

La ventaja grande. Para depurar el paso once no hace falta pasar por los diez
anteriores: se pone la conversación en ese estado y ya.

```sql
INSERT INTO conversaciones_pipe (telefono, estado, datos)
VALUES (
  '5215555555555',
  'esperando_descripcion',
  '{"nombre":"Prueba","edad":30,"gestion":{"tipo":"bache"}}'::jsonb
)
ON CONFLICT (telefono) DO UPDATE
SET estado = EXCLUDED.estado, datos = EXCLUDED.datos;
```

Un `POST` y estás probando exactamente el paso que te importa. De dos minutos de
tecleo a dos segundos.

## Un guion para el flujo completo

Con eso, la conversación entera cabe en un archivo que se puede correr las veces
que haga falta:

```bash
#!/usr/bin/env bash
set -e

URL="https://n8n.midominio.com/webhook/mi-bot"
TEL="5215555555555"

decir() {
  echo "→ $1"
  curl -sS -X POST "$URL" -H "Content-Type: application/json" \
    -d "{\"telefono\":\"$TEL\",\"texto\":\"$1\"}" | jq -r '.mensaje // "(sin respuesta)"'
  echo
}

psql -d n8n -c "DELETE FROM conversaciones_pipe WHERE telefono = '$TEL'"

decir "GIRA-TULA-0612"
decir "acepto"
decir "Manuel Cerda Bravo"
decir "37"
decir "42000"
decir "no"

psql -d n8n -c "SELECT estado, datos FROM conversaciones_pipe WHERE telefono = '$TEL'"
```

El `DELETE` inicial es lo que hace la prueba repetible: cada corrida arranca
desde cero. Y como el número no existe, no molesta a nadie.

Correr eso después de cada cambio en el workflow atrapa las regresiones que de
otro modo se descubren con un cliente enfrente.

## Los casos que hay que probar y nadie prueba

El camino feliz sale bien a la primera. Los que rompen bots son otros:

**Contestar cualquier cosa donde se espera un número.** «como treinta y cinco» en
la pregunta de la edad. El bot debe repreguntar, no guardar `NULL` y seguir.

**Mandar dos mensajes seguidos.** La gente escribe «Manuel» y luego «Cerda» en
mensajes separados. ¿Se concatena, se toma el último, se repregunta?

**Retomar a los tres días.** El estado sigue en la tabla. ¿El bot continúa donde
iba o debería reiniciar? Las dos respuestas son defendibles; lo que no es
defendible es no haberlo decidido.

**La palabra de escape en cada paso.** Si documentaste que `cancelar` sale, tiene
que salir desde los doce estados, no sólo desde el primero.

**Un número que ya completó el flujo y vuelve.** ¿Empieza de nuevo? ¿Se le dice
que ya está registrado?

Cada uno de esos es una línea más en el guion, y cada uno lo he visto fallar en
producción.

## Después sí, con WhatsApp real

Nada de esto reemplaza la prueba final con el número conectado, porque hay cosas
que sólo se ven ahí: cómo se corta un texto largo, si un botón cabe, si la imagen
se ve bien en un teléfono viejo, si el saludo automático de la app de WhatsApp
Business se encima con el tuyo.

Pero para cuando llegas a esa prueba, la lógica ya está bien. Y eso convierte la
sesión con WhatsApp real en una revisión de presentación en lugar de en una
cacería de errores a ciegas.

## El principio, que sirve para más que bots

Un bot es una integración, y una integración se prueba **desde el borde de tu
sistema hacia adentro**, no desde el sistema del otro.

En cuanto identificas cuál es tu frontera —aquí, el `POST` al webhook—, todo lo
que está de tu lado se puede ejercitar sin depender de nadie: sin cuenta
aprobada, sin cuota de mensajes, sin esperar a que Meta apruebe una plantilla, y
sin que un mensaje de prueba llegue al teléfono de un cliente real.
