# laravelconmanuel

Blog técnico y portafolio, con su panel de administración. Es el motor de
[laravelconmanuel.dev](https://laravelconmanuel.dev): el sitio donde escribo
sobre lo que construyo y donde enseño en qué he trabajado.

**Laravel 13 · PHP 8.3 · Inertia · React 19 · TypeScript · PostgreSQL**

---

## Qué hace

### El sitio público

Server-side con Blade, **sin una sola línea de JavaScript de framework**. Las
páginas de lectura son HTML plano con CSS: cargan al instante y se indexan sin
depender de que Google ejecute nada.

| Ruta | Qué es |
| --- | --- |
| `/` | Portada con lo destacado, lo reciente y lo más leído |
| `/articulos` · `/tutoriales` · `/recursos` | Los tres tipos de publicación, paginados |
| `/articulos/{slug}` | La publicación, con comentarios, descargas y relacionadas |
| `/categoria/{slug}` | Publicaciones de una categoría |
| `/buscar` | Búsqueda con atajo `⌘K` |
| `/manuel` | Ficha profesional: trayectoria, stack, formación y contacto |
| `/proyectos` | Los proyectos en producción, con su stack y acceso de demostración |
| `/newsletter` | Alta al boletín con doble confirmación |
| `/privacidad` | Aviso de privacidad (LFPDPPP) |
| `/feed` · `/sitemap.xml` · `/robots.txt` | RSS y lo que necesitan los buscadores |

Tema claro y oscuro con preferencia guardada, y diseño propio —nada de plantilla.

### El panel

Inertia + React + TypeScript, con Wayfinder para que las rutas del backend
lleguen al frontend como funciones tipadas: escribir mal el nombre de una ruta
deja de compilar en vez de dar un 404 en producción.

- **Publicaciones, tutoriales y recursos** comparten controlador, Form Request,
  Resource y página, parametrizados por tipo. Un campo nuevo se agrega una vez.
- **Editor de contenido** con subida de imágenes al disco S3.
- **Programación**: una publicación con fecha futura sale sola cuando toca.
- **Categorías y etiquetas**, **moderación de comentarios**, **suscriptores** y
  **mensajes de contacto** con respuesta por correo desde el propio panel.
- **Tablero** con gráficas de Highcharts: vistas por día y tipo, top de
  publicaciones, programadas y pendientes por atender.
- **Bitácora de visitas**: quién abrió qué, desde dónde llegó y con qué
  navegador.

### Los correos

Nueve, todos encolados: bienvenida y confirmación de suscripción, aviso de
publicación nueva, acuse y notificación de contacto, respuesta a contacto,
notificación y respuesta de comentario, y uno de prueba para verificar el SMTP
sin molestar a nadie.

---

## El entorno de redacción

Lo que más define a este proyecto. **Las publicaciones se escriben en Markdown,
se versionan con git y se sincronizan a la base.** El archivo es la fuente de
verdad, no la base de datos.

```
redaccion/
├── borradores/          un .md por publicación
├── investigacion/       las notas de campo detrás de cada texto
└── PLAN-EDITORIAL.md    qué está escrito, qué falta y por qué
```

Cada borrador es un Markdown con encabezado YAML:

```markdown
---
titulo: 'UUID en Laravel: por qué usarlos'
tipo: post
estado: publicado
categoria: Base de datos
etiquetas: [laravel, postgresql]
resumen: Una línea para el listado y las redes.
hace_dias: 4
---

El cuerpo, en Markdown.
```

```bash
php artisan blog:redaccion --revisar   # valida sin tocar la base
php artisan blog:redaccion             # publica los cambios
php artisan blog:redaccion --limpiar   # y borra lo que ya no tiene borrador
php artisan blog:exportar              # el camino de vuelta: de la base a .md
```

Es idempotente y empareja por `slug`. La fecha se guarda como antigüedad
relativa (`hace_dias`) y no como fecha fija, para que una base sembrada no
envejezca sola.

---

## Roles y permisos

Los permisos viven en un enum de PHP y el seeder los sigue, así que el catálogo
no se puede desincronizar del código. Formato `modulo.accion`.

**Publicar y eliminar tienen permiso propio**, separados de `gestionar`. Eso
permite un perfil que escribe pero no saca nada al aire:

| Rol | Puede |
| --- | --- |
| **Administrador** | Todo |
| **Editor** | Escribe, publica, elimina y modera. No toca usuarios ni roles |
| **Demostración** | Consulta y captura. No publica, no elimina y no ve datos personales de terceros |

El rol de demostración existe para que cualquiera recorra el panel desde
`/proyectos` sin pedir acceso. Sus credenciales salen del `.env`; vaciar
`DEMO_ACCESO_CLAVE` apaga la cuenta y el bloque de la vitrina de una vez.

---

## Arranque

Requiere PHP 8.3+, PostgreSQL y Node 20+.

```bash
git clone https://github.com/manuelcbravo/blog_completo.git
cd blog_completo

composer install
npm install
cp .env.example .env
php artisan key:generate

# ajusta DB_* en el .env
php artisan migrate
php artisan db:seed          # permisos, roles, admin, demo y las publicaciones

php artisan wayfinder:generate
npm run dev
```

Para producción, **[`DESPLIEGUE.md`](DESPLIEGUE.md)**: variables comentadas,
nginx, la cola, el programador y el alta en los buscadores.

---

## Generar un módulo

Un CRUD completo —migración, modelo, factory, controlador, Form Request,
Resource y página React con tabla, diálogo de alta y confirmación de borrado—
sale de un comando, siguiendo las convenciones del proyecto:

```bash
php artisan make:modulo Producto --grupo=inventario
```

Las plantillas están en `stubs/modulo/`. Ajústalas ahí y todos los módulos
futuros heredan el cambio.

---

## Calidad

```bash
php artisan test              # 150 pruebas
./vendor/bin/pint             # formato
./vendor/bin/phpstan analyse  # análisis estático
npx tsc --noEmit              # tipos del frontend
npm run lint
```

Las pruebas cubren el sitio público, los permisos por rol, la programación de
publicaciones, los correos, la bitácora de visitas y el entorno de redacción,
incluida una que valida los borradores reales del repositorio en cada corrida.

---

## Cómo está armado

```
app/
├── Enums/                  Permiso, Rol, TipoPublicacion, EstadoPublicacion…
├── Http/
│   ├── Controllers/
│   │   ├── Blog/           el panel
│   │   ├── Publico/        el sitio
│   │   └── Config/         usuarios y roles
│   ├── Requests/           validación por módulo
│   └── Resources/          el payload de Inertia
├── Mail/                   los nueve correos
├── Models/                 Publicacion es abstracta; Post, Tutorial y Recurso heredan
└── Support/
    ├── Redaccion/          el importador de borradores
    └── Publico/            utilidades de la vista pública

resources/
├── js/pages/               las páginas del panel
├── views/publico/          el sitio
└── css/                    publico.css, publico-autor.css, publico-proyectos.css

redaccion/                  las publicaciones, en Markdown
```

Dos convenciones que explican casi todo lo demás:

**`store` funciona como upsert.** Un controlador de CRUD expone `index`, `store`
y `destroy`; crear y editar son la misma ruta. El diálogo del frontend es uno
solo, con los campos llenos o vacíos.

**Los listados arman su payload con API Resources y `->resolve()`.** Un
`JsonResource` que llega a Inertia sin resolver se serializa envuelto en
`{data: [...]}` y rompe la página. Está documentado, y hay pruebas que fijan la
forma.

El resto está en [`CONVENCIONES.md`](CONVENCIONES.md).

---

## Licencia

Código bajo licencia MIT. **El contenido del blog y la ficha profesional son
míos y no se licencian**: si te sirve el motor, tómalo; los textos, no.
