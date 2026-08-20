---
titulo: 'Diseñar un bot que no canse: seis preguntas en lugar de diez'
slug: disenar-un-bot-de-whatsapp-que-no-canse
tipo: post
estado: borrador
categoria: Integraciones
etiquetas: [whatsapp, n8n, ux, bots, disenio]
resumen: Cada pregunta que le haces a alguien por WhatsApp es una oportunidad de que abandone. Tres decisiones que quitaron cuatro preguntas del flujo sin perder un solo dato.
meta_descripcion: 'Reducir la fricción de un bot de WhatsApp: parámetros en el enlace wa.me, inferencia de datos, preguntas que aceptan varios formatos y cuándo ofrecer lo opcional.'
hace_dias: 36
---

Un formulario web con diez campos se ve largo pero se contesta de un vistazo.
Diez preguntas por WhatsApp son diez mensajes, diez esperas y diez oportunidades
de que la persona deje la conversación a medias.

En el bot de captación en gira empecé con un flujo de diez preguntas. Quedó en
seis, sin perder un dato. Estas son las tres decisiones que lo hicieron.

## 1. El primer mensaje ya trae información

La gente llega al bot escaneando un QR o tocando una liga en un evento. Y una
liga de WhatsApp puede llevar texto prellenado:

```
https://wa.me/5217711318736?text=GIRA-TULA-0612
```

Al tocarla, WhatsApp abre la conversación con `GIRA-TULA-0612` ya escrito. La
persona sólo aprieta enviar.

Ese código le dice al bot **en qué evento está**, y de ahí sale el municipio, la
fecha y el equipo que organiza. Dos preguntas que no se hacen: «¿en qué evento
estás?» y, más adelante, «¿de qué municipio eres?».

Un QR por evento, impreso en la lona. Cero configuración del lado de quien
atiende.

Esto sirve para cualquier bot que se alcance desde un lugar físico o una campaña
concreta: el contexto que ya conoces no se pregunta, se codifica en el enlace.

## 2. Inferir lo que se puede inferir, y preguntar sólo lo dudoso

El sexo se infiere del nombre de pila. «María» es femenino, «Juan» es masculino,
y eso cubre la gran mayoría de los casos.

Pero hay nombres ambiguos —Guadalupe, Cruz, René, Refugio— y ahí el bot sí
pregunta.

```
PIPE_INFERIR_SEXO=1
```

La diferencia con adivinar es que **se sabe cuándo no se sabe**. Un diccionario
de nombres con una marca de ambigüedad, y la pregunta sale sólo cuando hace
falta.

La alternativa mala es inferir siempre y aceptar el error. En un padrón, un dato
demográfico incorrecto contamina cada reporte que se construya encima, y nadie lo
va a revisar nunca.

La otra alternativa mala es preguntar siempre. Cuesta una pregunta al 100% de la
gente para resolver un 5% de casos.

El criterio general: **inferir cuando la inferencia se puede verificar, preguntar
cuando no**. Es la misma regla que uso para el OCR de credenciales.

## 3. Una pregunta que acepta varias respuestas

La ubicación era el peor tramo del flujo original: municipio, colonia, calle y
código postal. Cuatro preguntas, cuatro esperas, y la mitad de la gente
abandonaba ahí.

Quedó en una:

> ¿Dónde estás? Puedes mandarme tu ubicación, tu código postal o simplemente
> escribirme la colonia.

El bot acepta las tres:

- **Ubicación de WhatsApp** → coordenadas, y con geocodificación inversa salen
  colonia, código postal, municipio y sección electoral de una vez.
- **Código postal** → cinco dígitos contra el catálogo.
- **Texto libre** → se busca contra el catálogo de colonias del municipio, que ya
  se conoce por el código de gira.

Tres formatos, una pregunta, y la persona usa el que le acomoda. Quien está
parado en el lugar manda la ubicación en dos toques; quien contesta desde su casa
escribe el nombre de su colonia.

La ganancia no es sólo de tiempo. Una pregunta abierta que acepta lo que sea se
siente como hablar con alguien; cuatro preguntas cerradas se sienten como un
formulario, y con razón.

## Y una de orden: lo opcional va al final

El bot registra a la persona y, opcionalmente, levanta una **gestión** —un
problema que quiere reportar—.

Al principio preguntaba por la gestión a media conversación. Terminó al final, en
una sola pregunta, y la razón está escrita en la documentación del proyecto:

> quien sólo quiere quedar registrado no tiene que inventarse un problema.

Preguntar por el problema a media captura crea presión social: la persona ya
invirtió cinco mensajes, la pregunta suena a que se espera algo de ella, y
contesta cualquier cosa. Acabas con gestiones inventadas que alguien tiene que
atender.

Al final y como opción explícita —«¿quieres reportar algo?»— el que dice que no
ya quedó registrado, que era el objetivo, y el que dice que sí es porque de
verdad tenía algo.

**Lo opcional al final, siempre.** Si va antes, deja de ser opcional en la
práctica.

## Lo que la plataforma te impone

Un apartado por si es tu primer bot de WhatsApp: la plataforma tiene
restricciones que cambian el diseño y no aparecen hasta que las chocas.

**El título de un botón interactivo no puede pasar de 20 caracteres.** Por eso el
botón de otro de mis bots dice «Asistente virtual» y no «Tomar pedido con
asistente virtual». Veinte caracteres es muy poco, y hay que escribirlos
pensando en que se lean solos.

**Los botones se limitan a tres opciones.** Más de tres es una lista, que se
despliega distinto y se siente más pesada. En cualquier paso de tres opciones o
menos, botones; a partir de ahí, lista.

**La app de WhatsApp Business puede tener su propio saludo automático**, y sale
antes que el tuyo. En un bot me aparecían dos saludos encimados hasta que caí en
que uno venía de la configuración de la app, no del código. Si el bot va a
saludar, hay que apagar el saludo de la app.

## Cómo lo mediría

No tengo métricas formales de abandono por paso — es lo primero que instrumentaría
si el bot creciera. La tabla de conversaciones ya guarda `estado`, así que un
conteo por estado de las conversaciones que llevan más de una hora sin moverse
dice exactamente en qué pregunta se cae la gente:

```sql
SELECT estado, count(*)
FROM conversaciones_pipe
WHERE actualizado < now() - interval '1 hour'
  AND estado <> 'cerrado'
GROUP BY estado
ORDER BY 2 DESC;
```

Esa consulta es el equivalente conversacional de un embudo, y sale gratis de una
tabla que ya existe. La incluyo aquí porque es lo que voy a correr yo la próxima
semana.
