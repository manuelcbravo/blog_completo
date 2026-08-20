---
titulo: El kit que reutilizo en cada proyecto (y cuándo lo suelto)
slug: el-kit-que-reutilizo-en-cada-proyecto
tipo: post
estado: borrador
categoria: Arquitectura
etiquetas: [laravel, arquitectura, productividad, sat]
resumen: Trece proyectos este año. Nueve modelos aparecen calcados en siete de ellos y en cero de otros cinco. Contar dónde está el kit y dónde no cuenta mejor la historia de lo que hago que cualquier currículum.
meta_descripcion: Cómo se construye un kit de arranque reutilizable en Laravel, qué debe incluir, y por qué la señal de madurez es saber cuándo no usarlo.
hace_dias: 47
---

Empecé trece proyectos Laravel este año. Cuando los puse uno junto al otro para
hacer inventario, apareció algo que no había medido nunca: hay nueve modelos que
son literalmente los mismos archivos en siete proyectos distintos.

`CatUsoCfdi`, `CatFormaPagoSat`, `CatRegimenFiscale`, `CatSatClaveUnidade`,
`CatSatServicio`, `CatEstado`, `CatMunicipio`, `Seguimiento`, `File`.

Y en otros cinco proyectos no está ninguno.

Esa asimetría es lo interesante, y es de lo que va este artículo.

## Qué hay en el kit

El kit no es un framework ni un paquete de Composer. Es un proyecto base que
clono y del que borro lo que no aplica. Tiene tres capas.

**La de dependencias.** Idéntica en los trece: `spatie/laravel-permission` para
roles, `owen-it/laravel-auditing` para auditoría, `laravel/fortify` para
autenticación, `inertiajs/inertia-laravel` con React, `laravel/wayfinder` para
rutas tipadas, `league/flysystem-aws-s3-v3` para archivos, `maatwebsite/excel`,
`codedge/laravel-fpdf` y `orangehill/iseed`.

**La de catálogos.** Los del SAT completos: usos de CFDI, formas de pago,
regímenes fiscales, claves de unidad, claves de servicio. Más el catálogo de
estados y municipios de México, que en este país se necesita en el 90% de las
aplicaciones y que nadie quiere volver a capturar.

**La de convenciones.** Un enum `Permiso` como fuente de verdad, controladores
con `index`/`store`/`destroy` donde `store` funciona como upsert, Form Requests
por módulo, API Resources para los listados, y componentes de frontend
—`DataTable`, `CrudFormDialog`, `ConfirmDeleteDialog`, campos de formulario—
que hacen que dos módulos distintos se vean y se comporten igual.

## Lo que gana

El arranque. Un módulo CRUD completo —migración, modelo, controlador, Form
Request, Resource, página con tabla, diálogo de alta y confirmación de borrado—
sale en minutos porque hay un generador que lo escribe siguiendo esas
convenciones. En este blog es `php artisan make:modulo`.

Pero el ahorro real no es el primer día, es el mes seis. Cuando vuelves a un
proyecto que no tocabas desde marzo y el archivo está donde esperas, con el
nombre que esperas y la forma que esperas, no hay que releer nada. Trece
proyectos con la misma estructura son, en la práctica, un solo proyecto grande
que conoces bien.

Y hay un beneficio que no anticipé: **los arreglos viajan**. Cuando encontré que
un Resource anidado llegaba al frontend envuelto en `{data: [...]}` y rompía la
página, la corrección y la regla escrita entraron al kit. Los proyectos
siguientes nacieron sin ese error.

## Dónde se rompió

Aquí está el hallazgo del inventario. El conteo de los nueve modelos:

| Proyecto | Del kit | Qué es |
| --- | --- | --- |
| Punto de venta | 9 / 9 | Ventas, inventario, facturación |
| Avalúos de vehículos | 9 / 9 | Inspección, inventario, venta |
| ERP de taller | 9 / 9 | Órdenes, Mercado Libre |
| Agenda clínica | 9 / 9 | Citas, expedientes, cobros |
| Mesa de ayuda | 9 / 9 | Tickets, cotizaciones |
| Reportes ciudadanos | 9 / 9 | Baches, cartografía |
| Gestión territorial | 1 / 9 | Solicitudes, promotores |
| Plataforma de gobierno | 3 / 9 | Obras, denuncias, eventos |
| Central de taxis | 0 / 9 | Servicios, operadores, cortes |
| Inteligencia electoral | 2 / 9 | Secciones, resultados |

La línea divide con una nitidez que no esperaba. **Los proyectos que facturan
traen el kit completo. Los de gobierno y territorio lo soltaron.**

Tiene todo el sentido. Los catálogos del SAT existen para timbrar un CFDI. Una
plataforma que registra solicitudes ciudadanas no emite facturas: esos veinte
catálogos serían veinte tablas vacías, veinte modelos que nadie abre y veinte
seeders que alargan cada instalación sin dar nada.

En la central de taxis, que sí factura, la decisión fue más fina: se quedó la
facturación —hay un `FacturaPdfRenderer` y un servicio de timbrado— pero se
construyó desde cero, con los modelos propios del dominio: `Servicio`,
`Operador`, `Taxi`, `Turno`, `Corte`, `Tarifa`, `ZonaTarifa`. El kit habría
metido una capa de abstracción entre el negocio y el código para ahorrar unas
horas.

## La lección, que no es la que esperaba

Iba a escribir un artículo sobre lo bueno que es reutilizar. El inventario me
hizo escribir otro.

Un kit de arranque tiene una fuerza gravitatoria. Está ahí, funciona, y usarlo
siempre es más cómodo que decidir. El peligro no es que el kit sea malo: es que
el proyecto termine amoldándose al kit en vez de al problema. Se nota cuando
aparece una tabla del kit sin filas, o un modelo que sólo existe porque venía
incluido.

**La señal de que un kit está sano no es cuántos proyectos lo usan completo, es
que haya proyectos que lo usaron a medias a propósito.** Un kit que se copia
íntegro trece veces no es un kit, es un molde, y el trabajo se parece más a
llenar formularios que a resolver problemas.

## Cómo lo estoy manejando ahora

Tres reglas que salieron de este ejercicio.

**Las convenciones sí van siempre; los catálogos no.** La estructura de
carpetas, los nombres, la forma de los controladores y los componentes de
frontend son gratis de arrastrar y se pagan solos. Las tablas de datos son una
decisión de dominio y se toman proyecto por proyecto.

**Lo que se instala, se usa el primer día.** Aprendí esto por la vía dolorosa
con la auditoría: el paquete estaba en los trece `composer.json` y sólo cuatro
modelos lo implementaban. Un kit que trae dependencias «por si acaso» genera la
ilusión de capacidades que no existen.

**Al mes de arrancar, se borra lo que no se usó.** Es la parte que más cuesta,
porque borrar código que funciona se siente mal. Pero una tabla vacía en el
esquema es una pregunta que alguien va a hacer dentro de dos años.
