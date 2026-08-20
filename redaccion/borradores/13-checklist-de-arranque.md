---
titulo: Checklist de arranque de un proyecto Laravel
slug: checklist-de-arranque-de-un-proyecto-laravel
tipo: recurso
estado: borrador
categoria: Arquitectura
etiquetas: [laravel, checklist, productividad, despliegue]
resumen: Las veintitantas decisiones que tomo antes de escribir la primera línea de negocio. Sale de trece proyectos arrancados este año y de las cosas que se me olvidaron en varios de ellos.
meta_descripcion: Checklist para arrancar un proyecto Laravel — herramientas de calidad, base de datos, permisos, archivos, correo, seguridad y despliegue.
hace_dias: 2
---

Arranqué trece proyectos Laravel este año. Cuando los revisé todos juntos,
encontré tres cosas que se me habían olvidado en varios: la auditoría instalada
pero sin modelos, exports sin `orderBy`, y más de un `.env.example` desfasado.

Esta lista es el resultado. La sigo antes de escribir la primera línea de lógica
de negocio, porque cada punto cuesta cinco minutos el primer día y una tarde el
mes seis.

## Antes de la primera migración

**Fijar las versiones.** Laravel 13 pide PHP 8.3. Escribirlo en `composer.json`
—`"php": "^8.3"`— evita que alguien despliegue en un servidor con 8.1 y lo
descubra en producción.

**Pint desde el commit uno.** `./vendor/bin/pint`. Discutir formato en una
revisión de código es tiempo tirado; que lo decida una herramienta.

**Análisis estático.** `composer require --dev larastan/larastan` y un
`phpstan.neon` en el nivel que aguante el equipo. Empieza en 5 y sube; empezar
en 9 sobre un proyecto real sólo consigue que alguien lo desactive.

**Pruebas corriendo.** `php artisan test` en verde antes de tener nada que
probar. Si arrancas con la suite rota, nunca se arregla.

**Idioma.** Si la interfaz es en español, `laravel-lang/common` desde el
principio. Traducir mensajes de validación a mano cuando ya hay cuarenta
formularios es trabajo perdido.

## Base de datos

**Elegir el motor de verdad.** PostgreSQL si vas a necesitar JSON con índices,
geografía o consultas analíticas. MySQL si el hosting manda. Cambiar después es
una migración de datos, no un cambio de configuración.

**Decidir los identificadores públicos.** Si algo va a aparecer en una URL, en
un QR o en una API para terceros, ese algo necesita un UUID junto al `id`.
Agregarlo cuando la tabla tiene datos son tres pasos y una migración con
cuidado; ponerlo desde el inicio es una línea.

**Convención de nombres, escrita.** Tablas en plural o singular, columnas en
español o inglés, llaves foráneas `id_cliente` o `cliente_id`. Da igual cuál,
pero decidido y anotado. La mezcla es lo que duele.

**Restricciones en la base, no sólo en el código.** Llaves foráneas, únicos y
`NOT NULL` declarados. Tu aplicación no es lo único que escribe en esa base:
también hay seeders, importaciones y, algún día, alguien con `psql`.

## Seguridad y accesos

**Los permisos en un enum, no en cadenas.** Una constante que el editor
autocompleta y que falla al compilar si la escribes mal, en vez de un `false`
silencioso repartido por catorce archivos.

**El seeder de roles sincroniza desde el enum.** Agregar un caso y correr el
seeder debe bastar para que el permiso exista en todos los entornos.

**Auditoría con al menos un modelo el primer día.** Instalar
`owen-it/laravel-auditing` no audita nada. Si no vas a marcar ningún modelo
todavía, no lo instales: un `composer.json` que promete auditoría y una tabla
vacía es peor que no tener nada.

**Comprobar el caso sin usuario, explícitamente.** `$request->user()?->cannot(...)`
devuelve `null` cuando no hay sesión, y `null` es falsy. Escribe
`$usuario === null || $usuario->cannot(...)`.

## Archivos

**Disco configurable, nunca `'public'` a mano.** `config('app.disco')` o el
nombre que uses, y que apunte a S3 o MinIO en producción y a local en
desarrollo. Un `Storage::disk('public')` incrustado en un controlador es la
razón por la que después no se puede escalar a dos servidores.

**Lista blanca de tipos en cada subida.** `['required','file','max:20480']` no
restringe nada: acepta HTML, SVG y JavaScript. Si los archivos se sirven con su
URL directa, eso es una página ejecutable en tu dominio.

**Región y endpoint del S3 verificados con una subida real.** El error
`IllegalLocationConstraint` aparece cuando el bucket está en una región distinta
de la configurada, y aparece el día que subes el primer archivo, no antes.

## Correo

**Cola desde el principio.** `Mail::to(...)->queue(...)`, nunca `->send()`. Un
correo síncrono son dos segundos que el usuario espera mirando el botón, y una
excepción del SMTP que tumba la petición entera.

**Un `MAIL_MAILER=log` en desarrollo** para no mandar correos de prueba a
direcciones reales de un seeder.

**Lo que va después del commit, envuelto.** Notificaciones, webhooks salientes,
trabajos encolados. Si fallan, se registran en el log; no deben hacer rollback
de lo que ya se guardó.

## Frontend

**TypeScript, no JavaScript.** Especialmente con Inertia: el contrato entre el
controlador y el componente es lo único que te avisa cuando alguien renombra un
campo.

**Rutas tipadas.** Wayfinder si el frontend es TypeScript. Y su directorio
generado al `.gitignore`, con el paso de generación en el despliegue.

**Componentes de formulario compartidos desde el primer módulo.** Campo,
etiqueta, error y `aria-invalid` en un solo lugar. Si el segundo módulo se
escribe con primitivos sueltos, ya nunca se unifica.

**Resources con `->resolve()`.** Un `JsonResource` que llega a Inertia sin
resolver se serializa envuelto en `{data: [...]}` y rompe la página. Y las
relaciones anidadas, con `relationLoaded` en vez de `whenLoaded`, para que la
clave nunca desaparezca.

## Antes del primer despliegue

**El `.env.example` completo y sin valores reales.** Cada variable que el código
lee debe estar ahí. Es la única documentación de configuración que la gente lee.

**El `.gitignore` revisado.** `.env`, `vendor`, `node_modules`, el directorio de
rutas generadas y cualquier carpeta con documentos personales. Una vez que algo
entra al historial de git, sacarlo es reescribir la historia.

**Aviso de privacidad si registras IPs.** En México la IP es dato personal bajo
la LFPDPPP. Si guardas visitas, formularios de contacto o comentarios, hace
falta la página y su enlace.

**Un usuario administrador cuya contraseña venga del entorno.** Nunca sembrada
en el código, ni siquiera «temporalmente».

**Backups probados.** No configurados: probados. Un respaldo que nunca se
restauró es una hipótesis.

## Lo que dejo para después, a propósito

Caché de consultas, Redis, escalado horizontal, CDN, WebSockets. Todo eso se
agrega cuando hay un problema medido que lo justifique.

La lista de arriba es distinta: son cosas que cuestan diez veces más si se
posponen. Esa es la única razón por la que están ahí.
