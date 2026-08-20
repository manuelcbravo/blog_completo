# Entorno de redacción

Aquí se escribe el blog. Un archivo Markdown por publicación, versionado con
git, y un comando que lo lleva a la base de datos.

**La fuente de verdad es el archivo, no la base de datos.** Si borras un
borrador y sincronizas con `--limpiar`, la publicación desaparece del sitio.
Eso es deliberado: evita que se acumule contenido fantasma que nadie recuerda
haber escrito, y es lo que sacó de la base las publicaciones de demostración.

## Cómo se trabaja

```bash
# Revisar que los borradores estén bien formados, sin tocar la base
php artisan blog:redaccion --revisar

# Publicar los cambios
php artisan blog:redaccion

# Publicar y borrar lo que ya no tiene borrador detrás
php artisan blog:redaccion --limpiar
```

> **`--limpiar` borra a fondo.** Enseña la lista y pregunta antes, pero si
> alguien escribió una publicación **directamente en el panel**, aquí no hay
> archivo que la respalde y la limpieza se la lleva. Antes de aceptar, mira la
> lista: si reconoces algo que quieres conservar, bájalo a un `.md` primero.
> `--force` se salta la pregunta y sólo debería usarse en un guion.

Al sembrar de cero, `BlogContenidoSeeder` hace lo mismo pero en silencio **y
sin preguntar**, así que no lo corras sobre una base donde alguien esté
escribiendo desde el panel:

```bash
php artisan db:seed --class=BlogContenidoSeeder
```

Si quieres ver qué se creó y qué se borró, usa el comando, no el seeder.

El comando es idempotente. Vuelve a correrlo las veces que quieras: crea lo
que falta y actualiza lo que cambió, emparejando por `slug`.

## Estructura

| Carpeta | Qué guarda |
| --- | --- |
| `borradores/` | Un `.md` por publicación. Es lo que se sincroniza. |
| `investigacion/` | Las notas de campo: qué se inspeccionó y qué se encontró. No se publica. |
| `PLAN-EDITORIAL.md` | Qué está escrito, qué falta y por qué. |

## Anatomía de un borrador

```markdown
---
titulo: UUID en Laravel, ¿por qué usarlos?
slug: uuid-en-laravel-por-que-usarlos
tipo: post
estado: publicado
categoria: Base de datos
etiquetas: [laravel, postgresql, api]
resumen: Una línea que se lee en el listado y en las redes.
meta_descripcion: Lo que ve Google. Si se omite, usa el resumen.
hace_dias: 12
importante: false
---

El cuerpo, en Markdown.
```

### Los campos

| Campo | Obligatorio | Qué hace |
| --- | --- | --- |
| `titulo` | sí | El título. |
| `tipo` | sí | `post`, `tutorial` o `recurso`. Decide en qué tabla y en qué sección del sitio cae. |
| `estado` | sí | `borrador`, `revision`, `programado`, `publicado`, `abajo`. Sólo `publicado` sale al sitio. |
| `slug` | no | Se deriva del título si no lo pones. **No lo cambies después de publicar**: es la URL. |
| `categoria` | no | Se crea sola si no existe. |
| `etiquetas` | no | Lista. Se crean solas. |
| `resumen` | no | Se muestra en listados, en el RSS y como descripción social. |
| `meta_titulo`, `meta_descripcion`, `tags_seo` | no | SEO. Caen al título, al resumen y a las etiquetas si se omiten. |
| `hace_dias` | no | Antigüedad **relativa a hoy**, no una fecha fija. Ver abajo. |
| `importante` | no | La destaca en la portada. |
| `tiempo_lectura` | no | En minutos. Si se omite se calcula a 200 palabras por minuto. |

### Por qué `hace_dias` y no una fecha

Una base sembrada con fechas fijas envejece sola: la siembras en agosto y en
diciembre el blog aparenta llevar cuatro meses muerto. Con `hace_dias`, cada
siembra reconstruye un archivo histórico coherente. El precio es que la fecha
se mueve si vuelves a sembrar de cero, lo cual da igual mientras el contenido
viva en estos archivos.

**En producción no corras el seeder de cero.** Usa `php artisan blog:redaccion`,
que actualiza sin recalcular la fecha de lo que ya estaba publicado... salvo
que cambies `hace_dias`. Si necesitas una fecha inamovible, publica desde el
panel y saca el borrador de esta carpeta.

## Lo que el comando no hace

- **No sube imágenes.** La imagen destacada se asigna desde el panel, porque
  el archivo va al disco de `config('blog.disco')` y no tendría sentido
  duplicarlo en el repositorio.
- **No toca los archivos adjuntos de un recurso.** Igual: se cargan desde el
  panel.
- **No inventa vistas ni comentarios.** Si el tablero se ve vacío en local es
  porque nadie ha entrado, que es la verdad.
