# Subir el sitio y que empiece a verse en Google

Todo lo que hay que hacer, en orden. Está escrito para hacerse de una sentada.

---

## 1. Antes de tocar el servidor

### Las variables del `.env` de producción

```dotenv
APP_NAME="laravelconmanuel"
APP_ENV=production
APP_DEBUG=false                      # crítico: en true, un error muestra tu .env
APP_URL=https://laravelconmanuel.dev # sin barra final, con https
APP_KEY=                             # php artisan key:generate

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database            # los correos van encolados
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="hola@laravelconmanuel.dev"
MAIL_FROM_NAME="${APP_NAME}"

BLOG_DISCO=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_ENDPOINT=https://s3.laravelconmanuel.dev
AWS_USE_PATH_STYLE_ENDPOINT=true

BLOG_ANUNCIOS=false                  # en true muestra los huecos de publicidad

ADMIN_SEED_PASSWORD=                 # obligatoria: sin ella el seeder aborta
DEMO_ACCESO_USUARIO=demo@laravelconmanuel.dev
DEMO_ACCESO_CLAVE=                   # vacía = no hay cuenta demo ni bloque en /proyectos
```

**`APP_URL` es la variable que más consecuencias tiene.** De ahí salen el
`canonical`, las URLs del sitemap, la dirección del `robots.txt`, los enlaces de
los correos y las etiquetas Open Graph. Si queda mal, Google indexa direcciones
que no existen.

### Comprobar en local

```bash
php artisan test          # 150 pruebas
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
npx tsc --noEmit
npm run build
```

---

## 2. El servidor

Requisitos: **PHP 8.3+**, PostgreSQL 17, Node 20+ para compilar, nginx, certificado TLS.

```bash
git clone https://github.com/manuelcbravo/blog_completo.git
cd blog_completo

composer install --no-dev --optimize-autoloader
npm ci

cp .env.example .env      # y llenarlo con lo de arriba
php artisan key:generate

php artisan migrate --force
php artisan db:seed --force     # permisos, roles, admin, demo y las publicaciones

php artisan wayfinder:generate  # antes de compilar: el frontend lo importa
npm run build

php artisan storage:link
```

### Las cachés de producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Con `config:cache` activo, **`env()` deja de funcionar fuera de los archivos de
configuración**. Todo el código de este proyecto lee por `config()`, así que no
hay problema, pero conviene saberlo antes de agregar código nuevo.

### La cola

Los ocho correos del blog salen encolados. Sin un trabajador corriendo, nadie
recibe nada y no hay ningún error visible.

```ini
# /etc/supervisor/conf.d/blog-worker.conf
[program:blog-worker]
command=php /var/www/blog/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/blog/storage/logs/worker.log
stopwaitsecs=3600
```

### El programador

Las publicaciones programadas se publican solas con una tarea del `scheduler`.
Sin esta línea, `estado: programado` se queda ahí para siempre:

```cron
* * * * * cd /var/www/blog && php artisan schedule:run >> /dev/null 2>&1
```

### Permisos de archivos

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 3. nginx

```nginx
server {
    listen 443 ssl http2;
    server_name laravelconmanuel.dev;
    root /var/www/blog/public;

    index index.php;
    charset utf-8;

    # El panel manda cabeceras grandes al iniciar sesión; sin esto sale un 502
    # que sólo aparece con la sesión abierta y vuelve loco a cualquiera.
    fastcgi_buffers 16 32k;
    fastcgi_buffer_size 64k;

    client_max_body_size 25M;   # las subidas de recursos llegan a 20 MB

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;
}

server {
    listen 80;
    server_name laravelconmanuel.dev www.laravelconmanuel.dev;
    return 301 https://laravelconmanuel.dev$request_uri;
}
```

**Un solo dominio canónico.** Elige con `www` o sin `www` y redirige el otro con
un 301. Servir el mismo contenido en los dos duplica todo tu sitio a ojos de
Google y reparte la autoridad entre dos direcciones.

---

## 4. Publicar el contenido

Las 39 publicaciones se siembran en estado `borrador`. **El sitio arranca
vacío**, a propósito: se revisa y se publica lo que esté listo.

Dos formas de publicar:

**Desde el panel**, una por una, con el menú «Estatus».

**Desde los archivos**, cambiando `estado: borrador` por `estado: publicado` en
`redaccion/borradores/*.md` y sincronizando:

```bash
php artisan blog:redaccion
```

Lo que se escriba directamente en el panel se baja a archivo con:

```bash
php artisan blog:exportar
```

---

## 5. Que Google lo encuentre

Nada de esto pasa solo. En orden de impacto:

### Google Search Console

1. Entra a [search.google.com/search-console](https://search.google.com/search-console)
   y agrega la propiedad **por dominio** (no por prefijo de URL: la de dominio
   cubre http, https, con y sin www).
2. Verifica con el registro **TXT** en tu DNS.
3. En **Sitemaps**, envía `https://laravelconmanuel.dev/sitemap.xml`.
4. En **Inspección de URLs**, pega la portada y pulsa **Solicitar indexación**.
   Repite con los tres o cuatro artículos que más te importen. Es la vía rápida:
   el rastreo natural puede tardar semanas.

### Bing Webmaster Tools

Importa la propiedad directamente desde Search Console en dos clics. Es tráfico
pequeño pero gratis, y además alimenta a DuckDuckGo y a varios asistentes de IA.

### Comprobar antes de pedir indexación

```bash
curl -s https://laravelconmanuel.dev/robots.txt
curl -s https://laravelconmanuel.dev/sitemap.xml | head -20
curl -s https://laravelconmanuel.dev/feed
```

Y con estas dos herramientas:

- **[Rich Results Test](https://search.google.com/test/rich-results)** sobre un
  artículo: debe reconocer `BlogPosting` y `BreadcrumbList`.
- **[PageSpeed Insights](https://pagespeed.web.dev/)**: mira las Core Web Vitals.
  El sitio no carga JavaScript en las páginas públicas, así que debería salir
  muy bien; lo que suele bajar la nota son imágenes sin optimizar.

### Lo que ya está resuelto en el código

| Qué | Dónde |
| --- | --- |
| `<title>`, `description` y `canonical` por página | `components/publico/layout.blade.php` |
| Open Graph y Twitter Card | igual |
| `article:published_time`, `author`, `section`, `tag` | igual, sólo en publicaciones |
| JSON-LD: `WebSite`, `Person`, `BlogPosting`, `BreadcrumbList` | `components/publico/datos-estructurados.blade.php` |
| `robots.txt` con la dirección del sitemap sacada de `APP_URL` | `FeedController::robots()` |
| Sitemap con todas las publicadas y su `lastmod` | `FeedController::sitemap()` |
| RSS | `FeedController::feed()` |
| `noindex` disponible por página | prop `noindex` del layout |

### Lo que depende de ti, no del código

**Imagen destacada en cada publicación.** Sin ella, lo que se comparte en
LinkedIn o WhatsApp sale sin miniatura y se ignora. Es el punto con más retorno
de toda esta lista, y hay que hacerlo desde el panel.

**Publicar seguido.** Un blog con tres artículos y sin movimiento se rastrea una
vez al mes. Uno que publica cada semana se rastrea cada pocos días.

**Enlaces desde fuera.** Tu perfil de LinkedIn, tu GitHub, tu firma de correo.
Es lo que hace que Google llegue por primera vez.

**Enlaces internos entre artículos.** Varias de las publicaciones se mencionan
entre sí; convertir esas menciones en enlaces reales reparte autoridad y mantiene
a la gente leyendo.

---

## 6. Después de desplegar, revisar

- [ ] `APP_DEBUG=false` — compruébalo entrando a una URL inexistente: debe salir
      la página de error, no la traza.
- [ ] `https://` fuerza redirección desde `http://`.
- [ ] El certificado cubre el dominio con y sin `www`.
- [ ] `/robots.txt` y `/sitemap.xml` responden y traen el dominio correcto.
- [ ] El formulario de contacto envía y llega el correo (la cola corriendo).
- [ ] La suscripción al newsletter manda su confirmación.
- [ ] Una subida de archivo llega a S3 y se puede descargar.
- [ ] Entrar con la cuenta demo y confirmar que **no puede publicar ni borrar**.
- [ ] `/privacidad` está enlazada desde el pie.
- [ ] Los respaldos de la base están configurados **y probados restaurando uno**.

---

## 7. Lo que conviene atender pronto

**Hay 15 avisos de seguridad en dependencias**, sobre 3 paquetes. Antes de
exponer el sitio:

```bash
composer audit
npm audit
```

Y actualizar lo que tenga arreglo disponible.
