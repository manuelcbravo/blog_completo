---
titulo: 'Laracollab: gestión de proyectos para equipos de desarrollo, en Laravel y React'
slug: laracollab-gestion-de-proyectos-laravel-react
tipo: post
estado: borrador
categoria: Herramientas
etiquetas: [laravel, react, inertia, codigo-abierto, herramientas]
resumen: Lo clonié para copiarle ideas, no para usarlo. Diecisiete modelos, Mantine en el frontend y una decisión sobre el registro de horas que me pareció mejor que la de las herramientas que pago.
meta_descripcion: Reseña de Laracollab, la alternativa de código abierto a Jira y Basecamp construida con Laravel, Inertia y React. Qué trae, cómo está construido y para quién sirve.
hace_dias: 76
---

Antes de aclarar nada: **no opero Laracollab en producción**. Lo clonié en
septiembre del año pasado, lo levanté, lo recorrí y me quedé leyendo el código.
Esta es una reseña de lectura, no de uso, y cualquier cosa que diga sobre cómo
se comporta con veinte personas dentro hay que tomarla con esa reserva.

Lo que sí puedo evaluar con propiedad es cómo está construido, que para un
proyecto de código abierto en el stack que uso todos los días es justamente lo
que me interesaba.

## Qué es

Una herramienta de gestión de proyectos para equipos de desarrollo, con el
enfoque puesto en el trabajo facturable: tareas, registro de horas y facturas
que salen de esas horas. Es código abierto y se autoaloja.

El dominio, mirando los modelos, son diecisiete:

`Project`, `Task`, `TaskGroup`, `TimeLog`, `Invoice`, `Comment`, `Attachment`,
`Label`, `Activity`, `ClientCompany`, `OwnerCompany`, `User`, `Role`,
`Permission`, `Currency`, `Country` y un directorio `Filters`.

Con eso se entiende el producto completo en treinta segundos, que ya dice algo
bueno del nombrado.

## El stack

Backend: **Laravel 11** con Inertia. Nada exótico. Los paquetes que trae dicen
más que la descripción:

| Paquete | Para qué |
| --- | --- |
| `laraveldaily/laravel-invoices` | Generación de facturas en PDF |
| `pusher/pusher-php-server` | Tiempo real: notificaciones y cambios en vivo |
| `spatie/laravel-permission` | Roles y permisos |
| `spatie/eloquent-sortable` | Reordenar tareas arrastrando |
| `owen-it/laravel-auditing` | Historial de cambios |
| `overtrue/laravel-favorite` | Marcar proyectos como favoritos |
| `lacodix/laravel-model-filter` | Filtros de listado |
| `intervention/image` | Miniaturas de adjuntos |
| `itsgoingd/clockwork` | Depuración |

Frontend: React, pero con **Mantine** en vez de Tailwind con shadcn, que es la
combinación más común hoy. Más `@hello-pangea/dnd` para arrastrar y soltar y
**Tiptap** como editor de texto enriquecido, con extensiones de menciones,
enlaces y resaltado.

## Lo que me pareció bien

**`spatie/eloquent-sortable` para el orden de las tareas.** Suena menor y no lo
es. Reordenar por arrastre parece trivial hasta que dos personas mueven tareas
al mismo tiempo y el orden se corrompe. Delegarlo a un paquete probado, en vez
de escribir un campo `orden` a mano con la lógica de reindexado, es exactamente
la decisión que yo tomaría.

**Tiptap para comentarios, con menciones.** Un comentario donde puedes escribir
`@manuel` y que le llegue una notificación es la diferencia entre una
herramienta que el equipo usa y una en la que sólo entra el jefe de proyecto.

**`TimeLog` como modelo de primera clase, no como un campo de `Task`.** Esta es
la que más me gustó. En las herramientas donde el tiempo es un número dentro de
la tarea, no puedes registrar que dos personas trabajaron la misma tarea en días
distintos, ni corregir una entrada sin pisar el total. Con una tabla propia, cada
registro tiene su autor, su fecha y su duración, y la factura se construye
sumando registros con su trazabilidad intacta.

Es una decisión de modelado que se ve pequeña en un diagrama y que determina si
la herramienta sirve para facturar de verdad.

**`OwnerCompany` y `ClientCompany` separadas.** La empresa que factura y la que
recibe no son la misma entidad con un booleano. Tener dos modelos evita un
montón de condicionales en la generación de facturas.

## Lo que no me convence

**Mantine.** No es peor que Tailwind, es distinto: componentes ya diseñados
contra utilidades de estilo. El problema es de encaje: si tu equipo trabaja con
Tailwind y shadcn —que es donde está la mayor parte del ecosistema Laravel hoy—,
adaptar Laracollab a tu identidad visual significa aprender otro sistema. Como
producto terminado da igual; como base para forkear, es un costo real.

**Pusher como dependencia.** Es un servicio de paga con capa gratuita. Para algo
que se autoaloja, chirría que el tiempo real dependa de una nube ajena. Se puede
cambiar por Reverb, que es el servidor de WebSockets de Laravel y corre en tu
máquina, pero es trabajo que tú haces.

**El registro de actividad es un modelo propio** (`Activity`) además de tener
`owen-it/laravel-auditing`. Dos historiales conviviendo. Puede haber una razón
—uno para mostrar en la interfaz, otro para auditoría fría— pero desde fuera
parece deuda.

## Para quién sirve

**Para una agencia o un equipo pequeño que factura por horas.** Ahí compite de
frente con herramientas de pago y gana en un punto que no es el precio: los
datos son tuyos y están en tu base, así que el reporte que necesitas lo escribes
con SQL en vez de pedirlo a soporte.

**Para aprender.** Es de los proyectos Laravel + Inertia + React de código
abierto más completos que hay. Si estás armando una aplicación con este stack,
leer cómo resuelve permisos, filtros, adjuntos y tiempo real vale más que veinte
tutoriales.

**No sirve** si necesitas flujos configurables, campos personalizados o
automatizaciones. Eso es territorio de Jira, y Laracollab no lo pelea. Tampoco
si tu equipo no tiene quién administre un servidor: es autoalojado, con todo lo
que eso implica.

## Lo que me llevé

No adopté la herramienta. Sí me llevé dos ideas.

La primera, la de `TimeLog` como entidad propia, que apliqué en una mesa de
ayuda donde el tiempo por actividad hay que facturarlo. Tenía el tiempo como
campo en la actividad y lo separé.

La segunda es más general: cuando evalúo una herramienta de código abierto en un
stack que domino, la pregunta útil no es «¿la voy a usar?» sino «¿qué decisiones
tomó su autor que yo no había considerado?». Con esa vara, una tarde leyendo
Laracollab rindió más que la mayoría de los cursos que he pagado.
