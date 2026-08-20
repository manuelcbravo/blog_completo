---
titulo: 'Bagisto: el framework de e-commerce en Laravel y Vue.js'
slug: bagisto-framework-ecommerce-laravel-vue
tipo: post
estado: revision
categoria: Herramientas
etiquetas: [laravel, vue, ecommerce, codigo-abierto]
resumen: Evaluación previa, no experiencia. Qué promete Bagisto, dónde encaja frente a montar la tienda a mano y qué pienso verificar antes de recomendarlo.
meta_descripcion: Qué es Bagisto, el framework de e-commerce de código abierto construido sobre Laravel y Vue.js, y en qué casos conviene frente a construir la tienda desde cero.
hace_dias: 0
---

> **Nota del autor.** Este texto está en revisión y no debería estar publicado.
> A diferencia del resto de lo que escribo aquí, **no he instalado Bagisto ni lo
> he operado**. Lo que sigue sale de su documentación y de su repositorio
> público, contrastado con mi experiencia construyendo comercio electrónico a
> mano. Lo dejo escrito para no perder el hilo, y lo publicaré cuando levante
> una tienda de prueba y pueda hablar con propiedad.

## Qué es

Bagisto es una plataforma de comercio electrónico de código abierto construida
sobre Laravel, con Vue.js en el frontend. No es un paquete que agregas a tu
aplicación: es una aplicación completa que extiendes.

Eso lo pone en la misma categoría que Magento o PrestaShop, no en la de un
carrito que instalas con Composer. La diferencia frente a esos dos es que el
código de abajo es Laravel, así que un desarrollador de Laravel puede leerlo
desde el primer día.

## Qué promete

Lo que anuncia como incluido: catálogo con productos configurables y agrupados,
multi-tienda, multi-moneda, multi-idioma, gestión de inventario por almacén,
carrito y pasarela, panel de administración, un sistema de temas y una
arquitectura de paquetes para extender sin tocar el núcleo.

Sobre el papel, eso es entre seis meses y un año de trabajo si lo construyes.

## Dónde creo que encaja

Mi experiencia es del otro lado: he construido comercio a mano. El punto de
venta que mantengo tiene canal web, catálogo, precios por lista, inventario por
almacén, cuentas por cobrar y facturación con CFDI. Son ciento dos modelos y no
llegamos ahí por gusto, sino porque el negocio pedía cosas que ninguna
plataforma traía.

Con esa vara, la pregunta con Bagisto es la de siempre en este tipo de
decisiones: **¿cuánto de lo que necesitas está en la caja, y cuánto vas a pelear
contra la caja?**

Si vendes productos con variantes, cobras con una pasarela estándar y envías con
una paquetería estándar, una plataforma te ahorra un año. Si tu negocio tiene
listas de precio por cliente, crédito, facturación fiscal mexicana y
sincronización con un ERP, cada una de esas piezas es una extensión, y llega un
punto donde extender cuesta más que haber construido.

## Lo que pienso verificar antes de recomendarlo

Cinco preguntas, en orden de importancia para el mercado en el que trabajo:

**Facturación CFDI.** Ninguna plataforma internacional trae timbrado mexicano.
Quiero ver qué tan limpio es engancharlo: si hay un punto de extensión claro
después de confirmar el pedido, o si hay que parchear el núcleo.

**El modelo de precios.** Si soporta listas de precio por cliente o grupo de
clientes de forma nativa. En venta B2B eso no es opcional y es de lo que peor
suelen llevarse las plataformas pensadas para venta al público.

**Inventario multi-almacén de verdad.** Que exista el concepto no basta:
importa cómo resuelve reservas, transferencias entre almacenes y qué pasa cuando
dos pedidos compiten por la última pieza.

**La arquitectura de paquetes.** Qué tanto se puede hacer sin tocar el núcleo,
porque de eso depende poder actualizar. Una tienda que no puede actualizarse
queda congelada en la versión con la que nació, y eso incluye sus parches de
seguridad.

**Vue.js.** Todo mi frontend es React con Inertia. Bagisto es Vue. Para el
storefront con tema propio da igual —es maquetación—, pero cualquier extensión
del panel de administración significa trabajar en un stack que no es el mío. No
es un impedimento, es un costo que hay que contar.

## Qué haría mientras tanto

Si alguien me preguntara hoy, sin haberlo probado, diría esto: **haz una prueba
de concepto de dos días con tu caso más raro**, no con el catálogo de ejemplo.
Toma la funcionalidad que sabes que es particular de tu negocio y trata de
implementarla como extensión. Si sale limpia, la plataforma te va a ahorrar
meses. Si a las dos horas estás editando archivos del núcleo, ya tienes tu
respuesta.

Es el mismo consejo que aplico a cualquier plataforma, y el que voy a seguir yo
mismo antes de convertir este borrador en un artículo.
