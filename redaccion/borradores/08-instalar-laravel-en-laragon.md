---
titulo: Instalar Laravel desde cero en Windows con Laragon
slug: instalar-laravel-desde-cero-en-windows-con-laragon
tipo: tutorial
estado: borrador
categoria: Primeros pasos
etiquetas: [laravel, windows, laragon, php, composer]
resumen: 'El entorno en el que trabajo todos los días, montado paso a paso: PHP 8.3, Composer, Laragon con dominios .test automáticos y PostgreSQL. Incluye los tres errores con los que todo el mundo se topa.'
meta_descripcion: 'Instalar Laravel en Windows con Laragon paso a paso: PHP, Composer, Node, dominios .test, base de datos y los errores más comunes al arrancar.'
hace_dias: 54
---

Todos mis proyectos viven en `C:\laragon\www`. Llevo años trabajando en Windows
con Laragon y, cada vez que alguien del equipo entra nuevo, repito estos mismos
pasos. Los dejo escritos.

Esto no es «la mejor manera» de correr Laravel —en producción todo esto va sobre
Linux con Docker—, es la manera en que un entorno local en Windows deja de
estorbar.

## Por qué Laragon y no XAMPP ni WSL

**Frente a XAMPP:** Laragon crea un dominio `.test` para cada carpeta que pongas
en `www`, con su virtual host y su certificado, sin que toques un archivo de
configuración. Creas `C:\laragon\www\mi-tienda` y `http://mi-tienda.test`
funciona. Con XAMPP eso son tres archivos editados a mano cada vez.

**Frente a WSL2:** WSL es más parecido a producción y es lo correcto para
proyectos con Docker. Pero el acceso a archivos entre Windows y WSL es lento, y
si tu editor está en Windows lo vas a notar en cada guardado. Para trabajar el
día entero sobre PHP, Laragon en nativo es más ágil.

## 1. Laragon

Se descarga la edición Full de [laragon.org](https://laragon.org). Trae Apache,
MySQL, PHP, Composer, Node y Git empaquetados.

Instálalo en `C:\laragon`. **No lo pongas en `Program Files`**: esa ruta tiene
espacio y permisos restringidos, y las dos cosas rompen herramientas de línea de
comandos en el momento menos oportuno.

Al abrirlo verás cuatro botones. El que importa es **Iniciar todo**, que levanta
Apache y la base de datos.

## 2. Poner la versión de PHP correcta

La que trae puede no ser la que necesitas. Laravel 13 pide **PHP 8.3 o
superior**; Laravel 12, PHP 8.2.

Laragon permite tener varias y cambiar entre ellas. Se descarga la que falte de
[windows.php.net](https://windows.php.net/download/) —la versión **Thread Safe**,
que es la que funciona con Apache— y se descomprime en:

```
C:\laragon\bin\php\php-8.3.24-Win32-vs16-x64\
```

Después, clic derecho en Laragon → **PHP** → **Version** → la nueva.

Comprobación:

```bash
php -v
```

Si dice 8.3 o más, listo. Si el comando no existe, Laragon no está agregando su
carpeta al PATH: abre la terminal desde el botón **Terminal** del propio Laragon
en vez de usar una consola cualquiera.

### Las extensiones

Laravel necesita varias. La mayoría vienen activadas, pero estas tres se olvidan
seguido:

```ini
; C:\laragon\bin\php\php-8.3.xx\php.ini
extension=fileinfo
extension=zip
extension=pdo_pgsql     ; si vas a usar PostgreSQL
extension=intl          ; para formato de fechas y números en español
```

Se descomentan quitando el `;`, y se reinicia Apache desde Laragon.

## 3. Composer

Laragon lo trae. Verifica:

```bash
composer -V
```

Si no está, se instala desde [getcomposer.org](https://getcomposer.org) con el
instalador de Windows, que detecta el PHP del PATH.

## 4. Crear el proyecto

Desde la terminal de Laragon, parado en `C:\laragon\www`:

```bash
composer create-project laravel/laravel mi-proyecto
```

O con el instalador oficial, que además pregunta por el kit de arranque:

```bash
composer global require laravel/installer
laravel new mi-proyecto
```

Tarda un par de minutos. Cuando termine:

```bash
cd mi-proyecto
```

Y ya puedes abrir **http://mi-proyecto.test** en el navegador. Sin configurar
nada: Laragon detectó la carpeta nueva y apuntó el dominio a `public/`.

Si no carga, clic derecho en Laragon → **Apache** → **Reload**.

## 5. La base de datos

Laragon trae MySQL. Yo uso PostgreSQL en todo, así que lo instalo aparte desde
[postgresql.org](https://www.postgresql.org/download/windows/) y lo dejo en el
puerto 5432.

La base se crea desde la terminal:

```bash
createdb -U postgres mi_proyecto
```

Y en el `.env` del proyecto:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=mi_proyecto
DB_USERNAME=postgres
DB_PASSWORD=tu_contraseña
```

Para MySQL con Laragon, la contraseña de `root` viene vacía:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mi_proyecto
DB_USERNAME=root
DB_PASSWORD=
```

Y se levantan las tablas:

```bash
php artisan migrate
```

## 6. El frontend

Laragon trae Node. Verifica con `node -v` que sea 20 o superior.

```bash
npm install
npm run dev
```

`npm run dev` levanta Vite y **se queda corriendo**. No lo cierres mientras
trabajas: es lo que recompila el CSS y el JavaScript al guardar. Necesitas dos
terminales abiertas, una para Vite y otra para los comandos de Artisan.

## Los tres errores con los que todo el mundo se topa

### «The stream or file could not be opened»

Permisos de escritura en `storage/` y `bootstrap/cache`. En Windows suele pasar
cuando el proyecto se creó con una cuenta y Apache corre con otra. Se arregla
dando control total al usuario sobre la carpeta del proyecto, desde
Propiedades → Seguridad.

### La página carga sin estilos

Vite no está corriendo, o el `APP_URL` no coincide con el dominio por el que
entras. Revisa que el `.env` diga:

```dotenv
APP_URL=http://mi-proyecto.test
```

Y después:

```bash
php artisan config:clear
```

Este es, con diferencia, el que más veces he tenido que explicar.

### «could not find driver»

La extensión de PDO de tu base no está activada. Es el `pdo_pgsql` o el
`pdo_mysql` del paso 2. Después de descomentarlo hay que **reiniciar Apache**,
no basta con guardar el `php.ini`.

Para confirmar qué PHP está usando la web —que puede no ser el de tu terminal—,
crea un archivo temporal en `public/`:

```php
<?php phpinfo();
```

Ábrelo, busca «Loaded Configuration File» y verás exactamente qué `php.ini` está
leyendo Apache. **Bórralo en cuanto termines**: `phpinfo()` publica la
configuración entera de tu servidor.

## Lo que hago siempre después de crear el proyecto

Tres cosas, antes de escribir la primera línea:

```bash
# Formato de código consistente desde el commit uno
./vendor/bin/pint

# Análisis estático
composer require --dev larastan/larastan
./vendor/bin/phpstan analyse

# Que las pruebas corran
php artisan test
```

Configurar esto cuando el proyecto tiene tres archivos toma cinco minutos.
Configurarlo cuando tiene doscientos es una tarde entera arreglando avisos.
