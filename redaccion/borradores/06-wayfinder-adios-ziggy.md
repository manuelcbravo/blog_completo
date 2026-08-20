---
titulo: 'Wayfinder: el año que dejé de usar Ziggy'
slug: wayfinder-el-ano-que-deje-de-usar-ziggy
tipo: post
estado: borrador
categoria: Frontend
etiquetas: [laravel, react, inertia, typescript, wayfinder]
resumen: Revisé trece proyectos. Los ocho de Laravel 12 tienen Ziggy. Los cinco de Laravel 13, ninguno. No fue una decisión, fue que route('algo.mal.escrito') dejó de compilar.
meta_descripcion: Wayfinder genera rutas de Laravel como funciones TypeScript tipadas. Qué cambia frente a Ziggy, cómo se instala con Inertia y React, y qué cuesta.
hace_dias: 39
---

Hice un inventario de mis trece proyectos Laravel de este año y salió un patrón
que no había notado.

Los ocho que arrancaron entre febrero y junio, sobre Laravel 12, tienen
`tightenco/ziggy` en el `composer.json`. Los cinco que arrancaron de julio en
adelante, sobre Laravel 13, no lo tienen ninguno. Los trece tienen
`laravel/wayfinder`.

No hubo un momento en que decidiera migrar. Simplemente, a partir de cierto
proyecto, dejé de agregar Ziggy porque ya no hacía falta.

## El problema que ambos resuelven

En una aplicación con Inertia, el backend define las rutas y el frontend tiene
que construir URLs para ellas. La opción mala es escribirlas a mano:

```tsx
router.get('/blog/contactos?estado=nuevo');
```

Eso se rompe el día que alguien cambia el prefijo de la ruta, y se rompe en
silencio: no hay error de compilación, sólo un 404 que aparece en producción.

**Ziggy** resuelve esto exportando la tabla de rutas de Laravel a JavaScript y
dando una función `route()`:

```tsx
route('blog.contactos.index', { estado: 'nuevo' });
```

Mejor. Pero ese primer argumento es una cadena. Si escribes
`blog.contacots.index`, TypeScript no dice nada: es una cadena válida. El error
sale en tiempo de ejecución.

## Lo que hace Wayfinder distinto

Wayfinder no exporta una tabla, **genera código**. Un archivo TypeScript por
grupo de rutas, con una función por ruta:

```bash
php artisan wayfinder:generate
```

Y en el componente:

```tsx
import { index, store, destroy } from '@/routes/blog/contactos';

router.get(index.url({ estado: 'nuevo' }));
form.post(store.url());
```

`index` es un símbolo importado. Si lo escribes mal, el editor lo subraya antes
de que guardes y la compilación falla. El error se movió de producción al
momento de teclear.

Y los parámetros van tipados. Si la ruta es
`blog/publicaciones/{tipo}/{publicacion}`, la función pide los dos, con sus
tipos. Olvidar uno no compila.

## Cómo se ve en un proyecto real

Este blog está hecho así. La página de mensajes de contacto empieza con:

```tsx
import { destroy, index, store } from '@/routes/blog/contactos';
```

y usa las tres a lo largo del archivo. Los formularios apuntan a `store.url()`,
los filtros a `index.url()`, el borrado a `destroy.url(registro.id)`.

El día que decidí que el CRUD funcionara como *upsert* —un solo `POST` que crea
o actualiza según venga el identificador— eliminé la ruta `update`. Al regenerar,
todos los `update.url()` del frontend dejaron de compilar de golpe, con su
archivo y su línea. Con Ziggy me habría enterado probando la aplicación a mano,
pantalla por pantalla.

## La instalación, completa

```bash
composer require laravel/wayfinder
```

En `vite.config.ts`:

```ts
import { wayfinder } from '@laravel/vite-plugin-wayfinder';

export default defineConfig({
    plugins: [
        laravel({ /* ... */ }),
        react(),
        wayfinder(),
    ],
});
```

Con el plugin, las rutas se regeneran solas cuando cambia `routes/*.php` y el
servidor de desarrollo está corriendo. En frío, o en el pipeline de despliegue,
se invoca el comando.

Un detalle de configuración que conviene resolver el primer día: el directorio
generado —`resources/js/routes` y `resources/js/actions`— **va al `.gitignore`**.
Es código derivado, como `vendor`. Versionarlo garantiza conflictos en cada
rebase.

Y por lo mismo, el despliegue necesita generarlo antes de compilar:

```bash
php artisan wayfinder:generate
npm run build
```

Si se te olvida, el build falla con «no se encuentra el módulo `@/routes/...`»,
que al menos es un error claro y no un 404 silencioso.

## Lo que cuesta

Ser honesto con lo que no me gusta.

**Es un paso más en la cadena de construcción.** Un desarrollador que clona el
repositorio y corre `npm run dev` sin haber hecho `composer install` y
`wayfinder:generate` se encuentra con un montón de importaciones rotas. La
solución es ponerlo en el `post-autoload-dump` del `composer.json` o
documentarlo en el README, pero es fricción que Ziggy no tenía.

**Genera muchos archivos.** Un proyecto con setenta rutas produce una estructura
de directorios que refleja los nombres de las rutas. Se navega bien, pero la
primera vez impresiona.

**Es de Laravel, no de la comunidad.** Eso hoy es una ventaja —se mantiene con
el framework— y mañana es un riesgo si el equipo cambia de opinión. Ziggy lleva
años y sobrevivió a varias versiones mayores.

## Cuándo seguiría usando Ziggy

Si el frontend no es TypeScript, Wayfinder pierde casi toda su gracia: sin
tipos, una función importada no es mucho mejor que una cadena. Ahí Ziggy es más
simple y hace lo mismo.

Y si tienes Blade con algo de JavaScript suelto, Ziggy encaja mejor: se inyecta
con una directiva y funciona. Wayfinder está pensado para un frontend compilado.

Para lo que yo hago —Inertia con React y TypeScript en todo— la elección dejó de
tener discusión. Es de esas herramientas que no notas hasta que vuelves a un
proyecto que no la tiene y escribes mal el nombre de una ruta.
